<?php

namespace App\Application\Services;

use App\Application\Interfaces\NotificacaoServiceInterface;
use App\Domain\Entities\Notificacao;
use App\Domain\Entities\Transacao;
use App\Domain\Entities\Usuario;
use App\Domain\Interfaces\ContaRepositoryInterface;
use App\Domain\Interfaces\NotificacaoRepositoryInterface;
use App\Domain\Interfaces\UsuarioRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificacaoService implements NotificacaoServiceInterface
{
    /**
     * @param NotificacaoRepositoryInterface $notificacaoRepository
     * @param ContaRepositoryInterface $contaRepository
     * @param UsuarioRepositoryInterface $usuarioRepository
     * @param AuditoriaService $auditoriaService
     */
    public function __construct(
        private NotificacaoRepositoryInterface $notificacaoRepository,
        private ContaRepositoryInterface $contaRepository,
        private UsuarioRepositoryInterface $usuarioRepository,
        private AuditoriaService $auditoriaService
    ) {
    }

    /**
     * Envia uma notificação após uma transação ser concluída.
     *
     * @param Transacao $transacao
     * @param bool $notificarRemetente Se deve notificar o remetente
     * @param bool $notificarDestinatario Se deve notificar o destinatário
     * @return void
     */
    public function notificarTransacaoConcluida(
        Transacao $transacao,
        bool $notificarRemetente = true,
        bool $notificarDestinatario = true
    ): void {
        try {
            // Obtém as contas envolvidas
            $contaOrigem = null;
            $contaDestino = null;
            $usuarioOrigem = null;
            $usuarioDestino = null;

            if ($transacao->detalheTransacao) {
                // Busca conta de origem e seu usuário
                if ($transacao->detalheTransacao->conta_origem_id && $notificarRemetente) {
                    $contaOrigem = $this->contaRepository->buscarPorId(
                        $transacao->detalheTransacao->conta_origem_id
                    );
                    
                    if ($contaOrigem) {
                        $usuarioOrigem = $this->usuarioRepository->buscarPorId($contaOrigem->usuario_id);
                    }
                }
                
                // Busca conta de destino e seu usuário
                if ($transacao->detalheTransacao->conta_destino_id && $notificarDestinatario) {
                    $contaDestino = $this->contaRepository->buscarPorId(
                        $transacao->detalheTransacao->conta_destino_id
                    );
                    
                    if ($contaDestino) {
                        $usuarioDestino = $this->usuarioRepository->buscarPorId($contaDestino->usuario_id);
                    }
                }
            }
            
            // Notificar o remetente
            if ($usuarioOrigem) {
                $this->criarNotificacaoTransacao(
                    $usuarioOrigem,
                    $transacao,
                    'Transferência realizada',
                    "Você realizou uma transferência de R$ " . number_format($transacao->amount, 2, ',', '.'),
                    [
                        'tipo_operacao' => 'saída',
                        'valor' => $transacao->amount,
                        'saldo_atual' => $contaOrigem->balance ?? 0
                    ]
                );
            }
            
            // Notificar o destinatário
            if ($usuarioDestino) {
                $this->criarNotificacaoTransacao(
                    $usuarioDestino,
                    $transacao,
                    'Transferência recebida',
                    "Você recebeu uma transferência de R$ " . number_format($transacao->amount, 2, ',', '.'),
                    [
                        'tipo_operacao' => 'entrada',
                        'valor' => $transacao->amount,
                        'saldo_atual' => $contaDestino->balance ?? 0
                    ]
                );
            }
            
            $this->auditoriaService->registrarAcao(
                'Notificações de transação enviadas',
                'transacao',
                [
                    'transacao_id' => $transacao->id,
                    'public_id' => $transacao->public_id,
                    'notificou_remetente' => $usuarioOrigem !== null,
                    'notificou_destinatario' => $usuarioDestino !== null
                ]
            );
        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação de transação', [
                'erro' => $e->getMessage(),
                'transacao_id' => $transacao->id
            ]);
            
            $this->auditoriaService->registrarErroOperacao(
                'Envio de notificação',
                'transacao',
                $e
            );
        }
    }
    
    /**
     * Envia uma notificação após uma transação falhar.
     *
     * @param Transacao $transacao
     * @return void
     */
    public function notificarTransacaoFalha(Transacao $transacao): void
    {
        try {
            // Busca o usuário que iniciou a transação
            $contaOrigemId = $transacao->detalheTransacao->conta_origem_id ?? null;
            
            if (!$contaOrigemId) {
                return;
            }
            
            $contaOrigem = $this->contaRepository->buscarPorId($contaOrigemId);
            
            if (!$contaOrigem) {
                return;
            }
            
            $usuario = $this->usuarioRepository->buscarPorId($contaOrigem->usuario_id);
            
            if (!$usuario) {
                return;
            }
            
            $mensagemErro = $transacao->error_message ?? 'Erro desconhecido';
            
            $this->criarNotificacaoTransacao(
                $usuario,
                $transacao,
                'Falha na transação',
                "Sua transação não pôde ser processada: {$mensagemErro}",
                [
                    'tipo_operacao' => 'falha',
                    'valor' => $transacao->amount,
                    'erro' => $mensagemErro
                ],
                Notificacao::TIPO_TRANSACAO_FALHA
            );
            
            $this->auditoriaService->registrarAcao(
                'Notificação de falha enviada',
                'transacao',
                [
                    'transacao_id' => $transacao->id,
                    'public_id' => $transacao->public_id,
                    'usuario_id' => $usuario->id,
                    'erro' => $mensagemErro
                ]
            );
        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação de falha', [
                'erro' => $e->getMessage(),
                'transacao_id' => $transacao->id
            ]);
            
            $this->auditoriaService->registrarErroOperacao(
                'Envio de notificação de falha',
                'transacao',
                $e
            );
        }
    }
    
    /**
     * Envia uma notificação após um estorno ser concluído.
     *
     * @param Transacao $transacao
     * @param Transacao $transacaoOriginal
     * @return void
     */
    public function notificarEstornoConcluido(
        Transacao $transacao,
        Transacao $transacaoOriginal
    ): void {
        try {
            // Notificar o usuário que recebeu o estorno (remetente original)
            if ($transacaoOriginal->detalheTransacao->conta_origem_id) {
                $contaOrigem = $this->contaRepository->buscarPorId(
                    $transacaoOriginal->detalheTransacao->conta_origem_id
                );
                
                if ($contaOrigem) {
                    $usuario = $this->usuarioRepository->buscarPorId($contaOrigem->usuario_id);
                    
                    if ($usuario) {
                        $this->criarNotificacaoTransacao(
                            $usuario,
                            $transacao,
                            'Estorno realizado',
                            "Sua transação foi estornada no valor de R$ " . number_format($transacao->amount, 2, ',', '.'),
                            [
                                'tipo_operacao' => 'estorno',
                                'valor' => $transacao->amount,
                                'transacao_original_id' => $transacaoOriginal->public_id,
                                'saldo_atual' => $contaOrigem->balance
                            ],
                            Notificacao::TIPO_ESTORNO_CONCLUIDO
                        );
                    }
                }
            }
            
            // Notificar o usuário que teve o valor estornado (destinatário original)
            if ($transacaoOriginal->detalheTransacao->conta_destino_id) {
                $contaDestino = $this->contaRepository->buscarPorId(
                    $transacaoOriginal->detalheTransacao->conta_destino_id
                );
                
                if ($contaDestino) {
                    $usuario = $this->usuarioRepository->buscarPorId($contaDestino->usuario_id);
                    
                    if ($usuario) {
                        $this->criarNotificacaoTransacao(
                            $usuario,
                            $transacao,
                            'Estorno recebido',
                            "Uma transação que você recebeu foi estornada no valor de R$ " . number_format($transacao->amount, 2, ',', '.'),
                            [
                                'tipo_operacao' => 'estorno_recebido',
                                'valor' => $transacao->amount,
                                'transacao_original_id' => $transacaoOriginal->public_id,
                                'saldo_atual' => $contaDestino->balance
                            ],
                            Notificacao::TIPO_ESTORNO_CONCLUIDO
                        );
                    }
                }
            }
            
            $this->auditoriaService->registrarAcao(
                'Notificações de estorno enviadas',
                'transacao',
                [
                    'transacao_id' => $transacao->id,
                    'public_id' => $transacao->public_id,
                    'transacao_original_id' => $transacaoOriginal->id
                ]
            );
        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação de estorno', [
                'erro' => $e->getMessage(),
                'transacao_id' => $transacao->id,
                'transacao_original_id' => $transacaoOriginal->id
            ]);
            
            $this->auditoriaService->registrarErroOperacao(
                'Envio de notificação de estorno',
                'transacao',
                $e
            );
        }
    }
    
    /**
     * Envia uma notificação sobre saldo baixo para o usuário.
     *
     * @param Usuario $usuario
     * @param float $saldo
     * @param string $numeroConta
     * @return void
     */
    public function notificarSaldoBaixo(
        Usuario $usuario,
        float $saldo,
        string $numeroConta
    ): void {
        try {
            // Define o limite abaixo do qual notificaremos o usuário
            $limiteParaAviso = 100.00; // R$ 100,00
            
            if ($saldo < $limiteParaAviso) {
                $this->notificacaoRepository->criar([
                    'usuario_id' => $usuario->id,
                    'tipo' => Notificacao::TIPO_SALDO_BAIXO,
                    'titulo' => 'Saldo baixo em sua conta',
                    'mensagem' => "O saldo da sua conta {$numeroConta} está baixo: R$ " . number_format($saldo, 2, ',', '.'),
                    'dados' => [
                        'saldo' => $saldo,
                        'numero_conta' => $numeroConta,
                        'limite_aviso' => $limiteParaAviso
                    ],
                    'canal' => Notificacao::CANAL_TODOS,
                    'recurso_tipo' => 'conta',
                    'recurso_id' => $numeroConta
                ]);
                
                // Tenta enviar email imediatamente
                $this->enviarEmail(
                    $usuario->email,
                    'Saldo baixo em sua conta',
                    "O saldo da sua conta {$numeroConta} está baixo: R$ " . number_format($saldo, 2, ',', '.') . 
                    "\n\nRecomendamos que você faça um depósito para evitar problemas com pagamentos futuros."
                );
                
                $this->auditoriaService->registrarAcao(
                    'Notificação de saldo baixo enviada',
                    'conta',
                    [
                        'usuario_id' => $usuario->id,
                        'numero_conta' => $numeroConta,
                        'saldo' => $saldo
                    ]
                );
            }
        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação de saldo baixo', [
                'erro' => $e->getMessage(),
                'usuario_id' => $usuario->id,
                'numero_conta' => $numeroConta
            ]);
            
            $this->auditoriaService->registrarErroOperacao(
                'Envio de notificação de saldo baixo',
                'conta',
                $e
            );
        }
    }
    
    /**
     * Envia uma notificação genérica para o usuário.
     *
     * @param Usuario $usuario
     * @param string $titulo
     * @param string $mensagem
     * @param array $dados
     * @param string $canal
     * @return void
     */
    public function enviarNotificacao(
        Usuario $usuario,
        string $titulo,
        string $mensagem,
        array $dados = [],
        string $canal = Notificacao::CANAL_EMAIL
    ): void {
        try {
            // Cria a notificação no banco de dados
            $notificacao = $this->notificacaoRepository->criar([
                'usuario_id' => $usuario->id,
                'tipo' => Notificacao::TIPO_GERAL,
                'titulo' => $titulo,
                'mensagem' => $mensagem,
                'dados' => $dados,
                'canal' => $canal
            ]);
            
            // Se o canal incluir email, envia email
            if ($canal === Notificacao::CANAL_EMAIL || $canal === Notificacao::CANAL_TODOS) {
                $this->enviarEmail($usuario->email, $titulo, $mensagem);
                $notificacao->marcarComoEnviada();
            }
            
            $this->auditoriaService->registrarAcao(
                'Notificação genérica enviada',
                'sistema',
                [
                    'usuario_id' => $usuario->id,
                    'titulo' => $titulo,
                    'canal' => $canal
                ]
            );
        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificação genérica', [
                'erro' => $e->getMessage(),
                'usuario_id' => $usuario->id,
                'titulo' => $titulo
            ]);
            
            $this->auditoriaService->registrarErroOperacao(
                'Envio de notificação genérica',
                'sistema',
                $e
            );
        }
    }
    
    /**
     * Cria uma notificação para transação.
     *
     * @param Usuario $usuario
     * @param Transacao $transacao
     * @param string $titulo
     * @param string $mensagem
     * @param array $dados
     * @param string $tipo
     * @return Notificacao
     */
    private function criarNotificacaoTransacao(
        Usuario $usuario,
        Transacao $transacao,
        string $titulo,
        string $mensagem,
        array $dados = [],
        string $tipo = Notificacao::TIPO_TRANSACAO_CONCLUIDA
    ): Notificacao {
        // Cria a notificação no banco de dados
        $notificacao = $this->notificacaoRepository->criar([
            'usuario_id' => $usuario->id,
            'tipo' => $tipo,
            'titulo' => $titulo,
            'mensagem' => $mensagem,
            'dados' => $dados,
            'canal' => Notificacao::CANAL_TODOS,
            'recurso_tipo' => 'transacao',
            'recurso_id' => $transacao->public_id
        ]);
        
        // Envia email imediatamente
        $this->enviarEmail($usuario->email, $titulo, $mensagem);
        $notificacao->marcarComoEnviada();
        
        return $notificacao;
    }
    
    /**
     * Envia um email.
     *
     * @param string $destinatario
     * @param string $assunto
     * @param string $mensagem
     * @return bool
     */
    private function enviarEmail(string $destinatario, string $assunto, string $mensagem): bool
    {
        try {
            // No ambiente de produção, enviaria um email real
            if (app()->environment('production')) {
                Mail::raw($mensagem, function ($message) use ($destinatario, $assunto) {
                    $message->to($destinatario)
                            ->subject($assunto);
                });
            } else {
                // Em ambiente de desenvolvimento, apenas loga
                Log::info("Email simulado: {$assunto}", [
                    'para' => $destinatario,
                    'assunto' => $assunto,
                    'mensagem' => $mensagem
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao enviar email', [
                'erro' => $e->getMessage(),
                'destinatario' => $destinatario,
                'assunto' => $assunto
            ]);
            
            return false;
        }
    }
} 