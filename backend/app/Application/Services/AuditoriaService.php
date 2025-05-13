<?php

namespace App\Application\Services;

use App\Domain\Interfaces\AuditoriaRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class AuditoriaService
{
    private AuditoriaRepositoryInterface $auditoriaRepository;

    public function __construct(AuditoriaRepositoryInterface $auditoriaRepository)
    {
        $this->auditoriaRepository = $auditoriaRepository;
    }
    /**
     * Registra uma ação de auditoria.
     *
     * @param string $acao Descrição da ação realizada
     * @param string $recurso Tipo de recurso envolvido
     * @param array $detalhes Detalhes adicionais da ação
     * @param string $nivel Nível de log (info, warning, error)
     * @param Request|null $request Objeto de requisição, se disponível
     * @return void
     */
    public function registrarAcao(
        string $acao,
        string $recurso,
        array $detalhes = [],
        string $nivel = 'info',
        ?Request $request = null
    ): void {
        $userId = Auth::id() ?? 'guest';
        $requestId = $request ? $request->header('X-Request-ID') : null;
        
        $dadosLog = [
            'acao' => $acao,
            'recurso' => $recurso,
            'usuario_id' => $userId,
            'request_id' => $requestId,
            'timestamp' => now()->toIso8601String(),
        ];
        
        // Adiciona informações da requisição, se disponível
        if ($request) {
            $dadosLog['ip'] = $request->ip();
            $dadosLog['method'] = $request->method();
            $dadosLog['url'] = $request->fullUrl();
            $dadosLog['user_agent'] = $request->userAgent();
        }
        
        // Adiciona detalhes específicos da ação
        $dadosLog['detalhes'] = $detalhes;
        
        // Grava no log com o nível apropriado
        switch ($nivel) {
            case 'emergency':
                Log::emergency('Auditoria: ' . $acao, $dadosLog);
                break;
            case 'alert':
                Log::alert('Auditoria: ' . $acao, $dadosLog);
                break;
            case 'critical':
                Log::critical('Auditoria: ' . $acao, $dadosLog);
                break;
            case 'error':
                Log::error('Auditoria: ' . $acao, $dadosLog);
                break;
            case 'warning':
                Log::warning('Auditoria: ' . $acao, $dadosLog);
                break;
            case 'notice':
                Log::notice('Auditoria: ' . $acao, $dadosLog);
                break;
            case 'debug':
                Log::debug('Auditoria: ' . $acao, $dadosLog);
                break;
            case 'info':
            default:
                Log::info('Auditoria: ' . $acao, $dadosLog);
                break;
        }
        
        // Salva o registro de auditoria no banco de dados
        $this->auditoriaRepository->criar($dadosLog);
    }
    
    /**
     * Registra uma tentativa de acesso não autorizado.
     *
     * @param string $recurso Tipo de recurso que tentou ser acessado
     * @param string $identificadorRecurso Identificador do recurso
     * @param Request $request Objeto de requisição
     * @return void
     */
    public function registrarTentativaAcessoNaoAutorizado(
        string $recurso,
        string $identificadorRecurso,
        Request $request
    ): void {
        $this->registrarAcao(
            'Tentativa de acesso não autorizado',
            $recurso,
            ['identificador_recurso' => $identificadorRecurso],
            'warning',
            $request
        );
    }
    
    /**
     * Registra uma operação bem-sucedida.
     *
     * @param string $operacao Tipo de operação realizada
     * @param string $recurso Tipo de recurso envolvido
     * @param array $detalhes Detalhes da operação
     * @param Request|null $request Objeto de requisição, se disponível
     * @return void
     */
    public function registrarOperacaoBemSucedida(
        string $operacao,
        string $recurso,
        array $detalhes = [],
        ?Request $request = null
    ): void {
        $this->registrarAcao(
            $operacao . ' bem-sucedida',
            $recurso,
            $detalhes,
            'info',
            $request
        );
    }
    
    /**
     * Registra um erro durante uma operação.
     *
     * @param string $operacao Tipo de operação realizada
     * @param string $recurso Tipo de recurso envolvido
     * @param \Throwable $erro Exceção capturada
     * @param Request|null $request Objeto de requisição, se disponível
     * @return void
     */
    public function registrarErroOperacao(
        string $operacao,
        string $recurso,
        \Throwable $erro,
        ?Request $request = null
    ): void {
        $this->registrarAcao(
            'Erro na operação: ' . $operacao,
            $recurso,
            [
                'erro_mensagem' => $erro->getMessage(),
                'erro_classe' => get_class($erro),
                'erro_arquivo' => $erro->getFile(),
                'erro_linha' => $erro->getLine(),
            ],
            'error',
            $request
        );
    }
    
    /**
     * Registra uma modificação de recurso.
     *
     * @param string $recurso Tipo de recurso modificado
     * @param string $identificadorRecurso Identificador do recurso
     * @param array $camposModificados Campos que foram modificados
     * @param Request|null $request Objeto de requisição, se disponível
     * @return void
     */
    public function registrarModificacaoRecurso(
        string $recurso,
        string $identificadorRecurso,
        array $camposModificados = [],
        ?Request $request = null
    ): void {
        $this->registrarAcao(
            'Modificação de ' . $recurso,
            $recurso,
            [
                'identificador_recurso' => $identificadorRecurso,
                'campos_modificados' => $camposModificados,
            ],
            'info',
            $request
        );
    }
    
    public function registrarEvento(array $dados): object
        {
            // Implementação do método registrarEvento
            $acao = $dados['acao'] ?? '';
            $descricao = $dados['descricao'] ?? 'Teste';
            $userId = $dados['usuario_id'] ?? null;
            $contaId = $dados['conta_id'] ?? null;


            // Validações básicas
            // if (empty($acao) || empty($descricao) || !$userId) {
            //     throw new InvalidArgumentException("Dados insuficientes para registrar evento de auditoria");
            // }
    
            // Cria o evento de auditoria
            $evento = $this->auditoriaRepository->criar([
                'acao' => $acao,
                'descricao' => $descricao,
                'user_id' => $userId,
                'recurso' => $dados['recurso'],
                // 'conta_id' => $contaId,
                'timestamp' => now()->toIso8601String()
            ]);
    
            return (object) ['sucesso' => true, 'evento' => $evento];
        }
}