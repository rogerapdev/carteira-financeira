<?php

namespace App\Application\Jobs;

use App\Domain\Entities\Notificacao;
use App\Domain\Interfaces\NotificacaoRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ProcessarNotificacoesPendentes implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60;
    
    /**
     * O número máximo de notificações a processar por execução.
     *
     * @var int
     */
    private int $limitePorExecucao;

    /**
     * Create a new job instance.
     */
    public function __construct(int $limitePorExecucao = 100)
    {
        $this->limitePorExecucao = $limitePorExecucao;
    }

    /**
     * Execute the job.
     */
    public function handle(NotificacaoRepositoryInterface $notificacaoRepository): void
    {
        $notificacoesPendentes = $notificacaoRepository->buscarPendentesDeEnvio($this->limitePorExecucao);
        
        if (empty($notificacoesPendentes)) {
            Log::info('Nenhuma notificação pendente para processar');
            return;
        }
        
        Log::info('Processando notificações pendentes', [
            'quantidade' => count($notificacoesPendentes)
        ]);
        
        foreach ($notificacoesPendentes as $notificacao) {
            try {
                $this->processarNotificacao($notificacao, $notificacaoRepository);
            } catch (\Exception $e) {
                Log::error('Erro ao processar notificação', [
                    'erro' => $e->getMessage(),
                    'notificacao_id' => $notificacao['id'] ?? 'desconhecido'
                ]);
            }
        }
    }
    
    /**
     * Processa uma notificação específica.
     *
     * @param array $dadosNotificacao
     * @param NotificacaoRepositoryInterface $repository
     * @return void
     */
    private function processarNotificacao(
        array $dadosNotificacao, 
        NotificacaoRepositoryInterface $repository
    ): void {
        $idNotificacao = $dadosNotificacao['id'] ?? null;
        
        if (!$idNotificacao) {
            Log::warning('Notificação sem ID identificado');
            return;
        }
        
        // Recupera a notificação completa do banco
        $notificacao = $repository->buscarPorId($idNotificacao);
        
        if (!$notificacao) {
            Log::warning('Notificação não encontrada', [
                'id' => $idNotificacao
            ]);
            return;
        }
        
        // Verifica o canal e envia de acordo
        $canal = $notificacao->canal ?? Notificacao::CANAL_EMAIL;
        $sucessoEnvio = false;
        
        if ($canal === Notificacao::CANAL_EMAIL || $canal === Notificacao::CANAL_TODOS) {
            $sucessoEnvio = $this->enviarEmail($notificacao);
        }
        
        if ($canal === Notificacao::CANAL_SMS || $canal === Notificacao::CANAL_TODOS) {
            // Implementação futura para SMS
            // $sucessoEnvio = $this->enviarSms($notificacao);
        }
        
        // Atualiza o status da notificação
        if ($sucessoEnvio) {
            $repository->marcarComoEnviada($idNotificacao);
            
            Log::info('Notificação enviada com sucesso', [
                'id' => $idNotificacao,
                'tipo' => $notificacao->tipo,
                'canal' => $canal
            ]);
        }
    }
    
    /**
     * Envia a notificação por email.
     *
     * @param Notificacao $notificacao
     * @return bool
     */
    private function enviarEmail(Notificacao $notificacao): bool
    {
        try {
            // Busca o email do usuário
            $usuario = $notificacao->usuario;
            
            if (!$usuario || !$usuario->email) {
                Log::warning('Usuário sem email para envio de notificação', [
                    'notificacao_id' => $notificacao->id,
                    'usuario_id' => $notificacao->usuario_id
                ]);
                return false;
            }
            
            // No ambiente de produção, envia email real
            if (app()->environment('production')) {
                Mail::raw($notificacao->mensagem, function ($message) use ($usuario, $notificacao) {
                    $message->to($usuario->email)
                            ->subject($notificacao->titulo);
                });
            } else {
                // Em ambiente de desenvolvimento, apenas loga
                Log::info("Email simulado: {$notificacao->titulo}", [
                    'para' => $usuario->email,
                    'assunto' => $notificacao->titulo,
                    'mensagem' => $notificacao->mensagem
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao enviar email de notificação', [
                'erro' => $e->getMessage(),
                'notificacao_id' => $notificacao->id
            ]);
            
            return false;
        }
    }
    
    /**
     * Handle a job failure.
     *
     * @param \Throwable $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Falha no processamento de notificações pendentes', [
            'erro' => $exception->getMessage(),
            'stack' => $exception->getTraceAsString()
        ]);
    }
} 