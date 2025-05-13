<?php

namespace App\Console\Commands;

use App\Application\Services\AuditoriaService;
use App\Domain\Entities\Conta;
use App\Domain\Entities\Transacao;
use App\Domain\Entities\Usuario;
use App\Domain\Interfaces\ContaRepositoryInterface;
use App\Domain\Interfaces\TransacaoRepositoryInterface;
use App\Domain\Interfaces\UsuarioRepositoryInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TestarAutorizacao extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'auth:test {usuario_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testa as políticas de autorização para acesso a recursos';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        private UsuarioRepositoryInterface $usuarioRepository,
        private ContaRepositoryInterface $contaRepository,
        private TransacaoRepositoryInterface $transacaoRepository,
        private AuditoriaService $auditoriaService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $userId = $this->argument('usuario_id');
        
        if (!$userId) {
            $usuarios = $this->usuarioRepository->listarTodos()->take(5);
            
            if ($usuarios->isEmpty()) {
                $this->error('Nenhum usuário encontrado no sistema.');
                return 1;
            }
            
            $choices = $usuarios->pluck('nome', 'id')->toArray();
            $userId = $this->choice('Selecione um usuário para testar autorização:', $choices);
        }
        
        $usuario = $this->usuarioRepository->buscarPorId($userId);
        
        if (!$usuario) {
            $this->error("Usuário com ID {$userId} não encontrado.");
            return 1;
        }
        
        // Simula a autenticação deste usuário
        Auth::login($usuario);
        
        $this->info("Testando políticas de autorização para o usuário: {$usuario->nome} (ID: {$usuario->id})");
        
        // Testa acesso a contas
        $this->testarAutorizacaoContas($usuario);
        
        // Testa acesso a transações
        $this->testarAutorizacaoTransacoes($usuario);
        
        // Logout
        Auth::logout();
        
        return 0;
    }
    
    /**
     * Testa autorização para recursos de conta.
     *
     * @param Usuario $usuario
     * @return void
     */
    private function testarAutorizacaoContas(Usuario $usuario)
    {
        $this->info("\nTestando acesso a CONTAS:");
        
        // Busca contas do usuário
        $contasDoUsuario = $this->contaRepository->buscarPorUsuarioId($usuario->id);
        
        if ($contasDoUsuario->isEmpty()) {
            $this->warn("  O usuário não possui contas para testar.");
            return;
        }
        
        // Pega a primeira conta do usuário para teste
        $contaDoUsuario = $contasDoUsuario->first();
        $this->info("  Conta do usuário: {$contaDoUsuario->numero} (ID: {$contaDoUsuario->id}, Public ID: {$contaDoUsuario->public_id})");
        
        // Testa se o usuário pode visualizar sua própria conta
        $autorizado = Gate::forUser($usuario)->allows('acessar-conta', $contaDoUsuario->public_id);
        $this->exibirResultadoAutorizacao('Visualizar própria conta', $autorizado);
        
        // Busca uma conta que não pertence ao usuário
        $contaDeOutroUsuario = $this->contaRepository->listarTodos()
            ->where('usuario_id', '!=', $usuario->id)
            ->first();
            
        if ($contaDeOutroUsuario) {
            $this->info("  Conta de outro usuário: {$contaDeOutroUsuario->numero} (ID: {$contaDeOutroUsuario->id}, Public ID: {$contaDeOutroUsuario->public_id})");
            
            // Testa se o usuário pode visualizar a conta de outro usuário
            $autorizado = Gate::forUser($usuario)->allows('acessar-conta', $contaDeOutroUsuario->public_id);
            $this->exibirResultadoAutorizacao('Visualizar conta de outro usuário', $autorizado);
        } else {
            $this->warn("  Não há contas de outros usuários para testar.");
        }
    }
    
    /**
     * Testa autorização para recursos de transação.
     *
     * @param Usuario $usuario
     * @return void
     */
    private function testarAutorizacaoTransacoes(Usuario $usuario)
    {
        $this->info("\nTestando acesso a TRANSAÇÕES:");
        
        // Busca contas do usuário
        $contasDoUsuario = $this->contaRepository->buscarPorUsuarioId($usuario->id);
        
        if ($contasDoUsuario->isEmpty()) {
            $this->warn("  O usuário não possui contas para testar transações.");
            return;
        }
        
        // Busca transações onde o usuário está envolvido
        $transacoes = collect();
        
        foreach ($contasDoUsuario as $conta) {
            $transacoesDaConta = $this->transacaoRepository->buscarTransacoesPorContaId($conta->id);
            $transacoes = $transacoes->merge($transacoesDaConta);
        }
        
        if ($transacoes->isEmpty()) {
            $this->warn("  O usuário não possui transações para testar.");
            return;
        }
        
        // Pega a primeira transação para teste
        $transacao = $transacoes->first();
        $this->info("  Transação do usuário: {$transacao->tipo} (ID: {$transacao->id}, Public ID: {$transacao->public_id})");
        
        // Testa se o usuário pode visualizar sua própria transação
        $autorizado = Gate::forUser($usuario)->allows('acessar-transacao', $transacao->public_id);
        $this->exibirResultadoAutorizacao('Visualizar própria transação', $autorizado);
        
        // Testa se o usuário pode estornar sua própria transação
        if ($transacao->tipo == Transacao::TIPO_TRANSFERENCIA) {
            $autorizado = Gate::forUser($usuario)->allows('estornar-transacao', $transacao->public_id);
            $this->exibirResultadoAutorizacao('Estornar própria transação', $autorizado);
        }
        
        // Busca uma transação que não envolve o usuário
        $contasIds = $contasDoUsuario->pluck('id')->toArray();
        $transacaoDeOutroUsuario = $this->transacaoRepository->listarTodos()
            ->whereDoesntHave('detalheTransacao', function ($query) use ($contasIds) {
                $query->whereIn('conta_origem_id', $contasIds)
                      ->orWhereIn('conta_destino_id', $contasIds);
            })
            ->first();
            
        if ($transacaoDeOutroUsuario) {
            $this->info("  Transação de outro usuário: {$transacaoDeOutroUsuario->tipo} (ID: {$transacaoDeOutroUsuario->id}, Public ID: {$transacaoDeOutroUsuario->public_id})");
            
            // Testa se o usuário pode visualizar a transação de outro usuário
            $autorizado = Gate::forUser($usuario)->allows('acessar-transacao', $transacaoDeOutroUsuario->public_id);
            $this->exibirResultadoAutorizacao('Visualizar transação de outro usuário', $autorizado);
            
            // Testa se o usuário pode estornar a transação de outro usuário
            if ($transacaoDeOutroUsuario->tipo == Transacao::TIPO_TRANSFERENCIA) {
                $autorizado = Gate::forUser($usuario)->allows('estornar-transacao', $transacaoDeOutroUsuario->public_id);
                $this->exibirResultadoAutorizacao('Estornar transação de outro usuário', $autorizado);
            }
        } else {
            $this->warn("  Não há transações de outros usuários para testar.");
        }
    }
    
    /**
     * Exibe o resultado da verificação de autorização.
     *
     * @param string $operacao
     * @param bool $autorizado
     * @return void
     */
    private function exibirResultadoAutorizacao(string $operacao, bool $autorizado)
    {
        $resultado = $autorizado ? '<fg=green>AUTORIZADO</>' : '<fg=red>NEGADO</>';
        $this->line("  {$operacao}: {$resultado}");
        
        // Registra na auditoria
        $this->auditoriaService->registrarAcao(
            "Teste de autorização: {$operacao}",
            'sistema',
            [
                'resultado' => $autorizado ? 'autorizado' : 'negado',
                'usuario_id' => Auth::id()
            ]
        );
    }
} 