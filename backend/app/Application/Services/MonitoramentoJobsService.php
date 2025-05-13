<?php

namespace App\Application\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class MonitoramentoJobsService
{
    /**
     * @var AuditoriaService
     */
    protected $auditoriaService;
    
    /**
     * @var NotificacaoService
     */
    protected $notificacaoService;
    
    /**
     * Constructor.
     *
     * @param AuditoriaService $auditoriaService
     * @param NotificacaoService $notificacaoService
     */
    public function __construct(
        AuditoriaService $auditoriaService,
        NotificacaoService $notificacaoService = null
    ) {
        $this->auditoriaService = $auditoriaService;
        $this->notificacaoService = $notificacaoService;
    }
    
    /**
     * Registra o início da execução de um job.
     *
     * @param string $jobId ID único do job
     * @param string $jobName Nome da classe do job
     * @param array $dados Dados relacionados ao job
     * @return string O ID do job
     */
    public function registrarInicioJob(string $jobId, string $jobName, array $dados = []): string
    {
        $jobId = $jobId ?: (string) Str::uuid();
        $timestamp = now()->toIso8601String();
        
        $metadados = [
            'job_id' => $jobId,
            'job_name' => $jobName,
            'status' => 'iniciado',
            'inicio' => $timestamp,
            'dados' => $dados
        ];
        
        // Registra em log
        Log::info("Job iniciado: {$jobName}", $metadados);
        
        // Registra na auditoria
        $this->auditoriaService->registrarAcao(
            "Job iniciado",
            'job',
            $metadados
        );
        
        // Armazena metadados do job em cache (Redis)
        if ($this->redisDisponivel()) {
            Redis::hset("job:{$jobId}", 'status', 'iniciado');
            Redis::hset("job:{$jobId}", 'job_name', $jobName);
            Redis::hset("job:{$jobId}", 'inicio', $timestamp);
            Redis::hset("job:{$jobId}", 'dados', json_encode($dados));
            Redis::expire("job:{$jobId}", 86400); // 24 horas
            
            // Adiciona à lista de jobs ativos
            Redis::sadd('jobs:ativos', $jobId);
        }
        
        return $jobId;
    }
    
    /**
     * Registra o término bem-sucedido de um job.
     *
     * @param string $jobId ID do job
     * @param string $jobName Nome da classe do job
     * @param array $resultado Resultado da execução
     * @return void
     */
    public function registrarSuccessoJob(string $jobId, string $jobName, array $resultado = []): void
    {
        $timestamp = now()->toIso8601String();
        
        $metadados = [
            'job_id' => $jobId,
            'job_name' => $jobName,
            'status' => 'concluido',
            'termino' => $timestamp,
            'resultado' => $resultado
        ];
        
        // Registra em log
        Log::info("Job concluído com sucesso: {$jobName}", $metadados);
        
        // Registra na auditoria
        $this->auditoriaService->registrarAcao(
            "Job concluído",
            'job',
            $metadados
        );
        
        // Atualiza metadados do job no cache
        if ($this->redisDisponivel()) {
            Redis::hset("job:{$jobId}", 'status', 'concluido');
            Redis::hset("job:{$jobId}", 'termino', $timestamp);
            Redis::hset("job:{$jobId}", 'resultado', json_encode($resultado));
            
            // Remove da lista de jobs ativos
            Redis::srem('jobs:ativos', $jobId);
            // Adiciona à lista de jobs concluídos
            Redis::sadd('jobs:concluidos', $jobId);
            Redis::expire("job:{$jobId}", 86400); // 24 horas
        }
    }
    
    /**
     * Registra a falha de um job.
     *
     * @param string $jobId ID do job
     * @param string $jobName Nome da classe do job
     * @param \Throwable $exception Exceção que causou a falha
     * @param array $dados Dados relacionados ao job
     * @param bool $enviarAlerta Se deve enviar alerta
     * @return void
     */
    public function registrarFalhaJob(
        string $jobId, 
        string $jobName, 
        \Throwable $exception, 
        array $dados = [],
        bool $enviarAlerta = true
    ): void {
        $timestamp = now()->toIso8601String();
        
        $metadados = [
            'job_id' => $jobId,
            'job_name' => $jobName,
            'status' => 'falha',
            'termino' => $timestamp,
            'erro' => [
                'mensagem' => $exception->getMessage(),
                'tipo' => get_class($exception),
                'arquivo' => $exception->getFile(),
                'linha' => $exception->getLine(),
                'stack' => $exception->getTraceAsString()
            ],
            'dados' => $dados
        ];
        
        // Registra em log
        Log::error("Job falhou: {$jobName}", $metadados);
        
        // Registra na auditoria
        $this->auditoriaService->registrarAcao(
            "Job falhou",
            'job',
            $metadados,
            'error'
        );
        
        // Atualiza metadados do job no cache
        if ($this->redisDisponivel()) {
            Redis::hset("job:{$jobId}", 'status', 'falha');
            Redis::hset("job:{$jobId}", 'termino', $timestamp);
            Redis::hset("job:{$jobId}", 'erro', json_encode([
                'mensagem' => $exception->getMessage(),
                'tipo' => get_class($exception)
            ]));
            
            // Remove da lista de jobs ativos
            Redis::srem('jobs:ativos', $jobId);
            // Adiciona à lista de jobs com falha
            Redis::sadd('jobs:falhas', $jobId);
            Redis::expire("job:{$jobId}", 259200); // 3 dias (mais tempo para análise)
            
            // Incrementa contador de falhas
            Redis::incr('jobs:contador_falhas');
        }
        
        // Envia alerta de falha, se configurado
        if ($enviarAlerta) {
            $this->enviarAlertaFalhaJob($jobId, $jobName, $exception, $dados);
        }
    }
    
    /**
     * Envia alerta sobre falha de job para administradores.
     *
     * @param string $jobId ID do job
     * @param string $jobName Nome da classe do job
     * @param \Throwable $exception Exceção que causou a falha
     * @param array $dados Dados relacionados ao job
     * @return void
     */
    protected function enviarAlertaFalhaJob(
        string $jobId, 
        string $jobName, 
        \Throwable $exception, 
        array $dados = []
    ): void {
        // Tenta enviar notificação se o serviço estiver disponível
        if ($this->notificacaoService) {
            // Notificação para administradores cadastrados
            try {
                // Obter emails de administradores (poderia vir de uma configuração)
                $emailsAdmins = config('jobs.admin_emails', []);
                $ambiente = app()->environment();
                
                if (!empty($emailsAdmins)) {
                    foreach ($emailsAdmins as $email) {
                        // Envia email direto, sem passar pelo sistema de notificações
                        $assunto = "[{$ambiente}] Falha de job: {$jobName}";
                        $mensagem = "Um job falhou em {$ambiente}:\n\n" .
                            "Job: {$jobName}\n" .
                            "ID: {$jobId}\n" .
                            "Erro: {$exception->getMessage()}\n" .
                            "Tipo: " . get_class($exception) . "\n" .
                            "Arquivo: {$exception->getFile()}:{$exception->getLine()}\n\n" .
                            "Verificar o log para mais detalhes.";
                            
                        $this->enviarEmailAlerta($email, $assunto, $mensagem);
                    }
                }
            } catch (\Exception $e) {
                Log::error('Erro ao enviar alerta de falha de job', [
                    'job_id' => $jobId,
                    'job_name' => $jobName,
                    'erro_notificacao' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Envia um email de alerta.
     *
     * @param string $destinatario Email do destinatário
     * @param string $assunto Assunto do email
     * @param string $mensagem Conteúdo do email
     * @return bool
     */
    protected function enviarEmailAlerta(string $destinatario, string $assunto, string $mensagem): bool
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
                Log::info("Email de alerta simulado: {$assunto}", [
                    'para' => $destinatario,
                    'assunto' => $assunto,
                    'mensagem' => $mensagem
                ]);
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error('Erro ao enviar email de alerta', [
                'erro' => $e->getMessage(),
                'destinatario' => $destinatario,
                'assunto' => $assunto
            ]);
            
            return false;
        }
    }
    
    /**
     * Obtém estatísticas dos jobs.
     *
     * @return array
     */
    public function obterEstatisticasJobs(): array
    {
        $estatisticas = [
            'ativos' => 0,
            'concluidos_24h' => 0,
            'falhas_24h' => 0,
            'falhas_total' => 0,
            'tempo_medio' => 0
        ];
        
        if ($this->redisDisponivel()) {
            // Conta jobs ativos
            $estatisticas['ativos'] = Redis::scard('jobs:ativos');
            
            // Contador de falhas total
            $estatisticas['falhas_total'] = (int)Redis::get('jobs:contador_falhas') ?: 0;
            
            // Jobs concluídos nas últimas 24h
            $jobsConcluidos = Redis::smembers('jobs:concluidos');
            $jobsFalhas = Redis::smembers('jobs:falhas');
            
            // Calcula estatísticas mais complexas
            $timestamps24h = now()->subDay()->timestamp;
            $duracoes = [];
            
            // Processa jobs concluídos
            foreach ($jobsConcluidos as $jobId) {
                $termino = Redis::hget("job:{$jobId}", 'termino');
                if ($termino && strtotime($termino) >= $timestamps24h) {
                    $estatisticas['concluidos_24h']++;
                    
                    // Calcula duração
                    $inicio = Redis::hget("job:{$jobId}", 'inicio');
                    if ($inicio) {
                        $duracao = strtotime($termino) - strtotime($inicio);
                        $duracoes[] = $duracao;
                    }
                }
            }
            
            // Processa falhas
            foreach ($jobsFalhas as $jobId) {
                $termino = Redis::hget("job:{$jobId}", 'termino');
                if ($termino && strtotime($termino) >= $timestamps24h) {
                    $estatisticas['falhas_24h']++;
                }
            }
            
            // Calcula tempo médio de execução
            if (!empty($duracoes)) {
                $estatisticas['tempo_medio'] = array_sum($duracoes) / count($duracoes);
            }
        }
        
        return $estatisticas;
    }
    
    /**
     * Verifica se o Redis está disponível.
     *
     * @return bool
     */
    protected function redisDisponivel(): bool
    {
        try {
            return Redis::connection()->ping() == 'PONG';
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Limpa dados de monitoramento antigos.
     *
     * @return int Número de registros removidos
     */
    public function limparDadosAntigos(): int
    {
        $removidos = 0;
        
        if ($this->redisDisponivel()) {
            // Limpa jobs concluídos com mais de 24 horas
            $jobsConcluidos = Redis::smembers('jobs:concluidos');
            $timestamp24h = now()->subDay()->timestamp;
            
            foreach ($jobsConcluidos as $jobId) {
                $termino = Redis::hget("job:{$jobId}", 'termino');
                if ($termino && strtotime($termino) < $timestamp24h) {
                    Redis::srem('jobs:concluidos', $jobId);
                    Redis::del("job:{$jobId}");
                    $removidos++;
                }
            }
            
            // Limpa jobs com falha com mais de 7 dias
            $jobsFalhas = Redis::smembers('jobs:falhas');
            $timestamp7d = now()->subDays(7)->timestamp;
            
            foreach ($jobsFalhas as $jobId) {
                $termino = Redis::hget("job:{$jobId}", 'termino');
                if ($termino && strtotime($termino) < $timestamp7d) {
                    Redis::srem('jobs:falhas', $jobId);
                    Redis::del("job:{$jobId}");
                    $removidos++;
                }
            }
        }
        
        return $removidos;
    }
} 