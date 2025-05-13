<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Domain\Entities\Usuario;
use App\Domain\Entities\Conta;
use App\Domain\Entities\Transacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;

class TransacaoControllerTest extends TestCase
{
    use DatabaseMigrations;

   /**
     * Setup before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure any previous transactions are completed
        DB::rollBack();
        
        // Make sure we're not in a transaction
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
    }

    /**
     * Cleanup after each test
     */
    protected function tearDown(): void
    {
        // Ensure any transactions from this test are completed
        while (DB::transactionLevel() > 0) {
            DB::rollBack();
        }
        
        parent::tearDown();
    }


    /**
     * Setup authentication for tests
     *
     * @param Usuario $usuario
     * @return string
     */
   protected function setupAuth(Usuario $usuario)
    {
        // Reset any previous authentication
        $this->app['auth']->forgetGuards();

        // Find the corresponding User model
        $user = Usuario::find($usuario->id);
        
        if (!$user) {
            throw new \Exception("Could not find User model for Usuario with ID {$usuario->id}");
        }
        
        // Create an actual token
        $token = $user->createToken('api-token')->plainTextToken;
        
        // Also set up Sanctum authentication for the current request
        Sanctum::actingAs($user, ['*']);
        
        return $token;
    }


    public function test_deve_realizar_transferencia_com_sucesso(): void
    {
        // Arrange
        $remetente = Usuario::factory()->create();
        $contaRemetente = Conta::factory()->create([
            'user_id' => $remetente->id,
            'balance' => 1000,
            'status' => 'active'
        ]);

        $destinatario = Usuario::factory()->create();
        $contaDestinatario = Conta::factory()->create([
            'user_id' => $destinatario->id,
            'balance' => 0,
            'status' => 'active'
        ]);

        $requestId = Str::uuid();
        $token = $this->setupAuth($remetente);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'X-Request-ID' => $requestId,
        ])->postJson('/api/transacoes/transferir', [
            'from_account_id' => $contaRemetente->public_id,
            'to_account_id' => $contaDestinatario->public_id,
            'amount' => 500,
            'description' => 'Transferência via API',
            'transaction_key' => $requestId
        ]);

        // Assert
        $response->assertStatus(202)
            ->assertJson([
                'message' => 'Transferência enfileirada para processamento',
                'from_account_id' => $contaRemetente->public_id,
                'to_account_id' => $contaDestinatario->public_id,
                'amount' => 500,
                'status' => 'pending',
                'transaction_key' => $requestId
            ]);
    }

    public function test_deve_retornar_erro_para_transferencia_sem_saldo(): void
    {
        // Arrange
        $remetente = Usuario::factory()->create();
        $contaRemetente = Conta::factory()->create([
            'user_id' => $remetente->id,
            'balance' => 100,
            'status' => 'active'
        ]);

        $destinatario = Usuario::factory()->create();
        $contaDestinatario = Conta::factory()->create([
            'user_id' => $destinatario->id,
            'balance' => 0,
            'status' => 'active'
        ]);

        $requestId = Str::uuid();
        $token = $this->setupAuth($remetente);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'X-Request-ID' => $requestId,
        ])->postJson('/api/transacoes/transferir', [
            'from_account_id' => $contaRemetente->public_id,
            'to_account_id' => $contaDestinatario->public_id,
            'amount' => 500,
            'description' => 'Transferência que deve falhar',
            'transaction_key' => $requestId
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Saldo insuficiente para realizar esta transferência',
                'amount' => '500,00',
                'fault' => '400,00'
            ]);
    }

    public function test_nao_deve_permitir_transferencia_para_conta_inexistente(): void
    {
        // Arrange
        $remetente = Usuario::factory()->create();
        $contaRemetente = Conta::factory()->create([
            'user_id' => $remetente->id,
            'balance' => 1000,
            'status' => 'active'
        ]);

        $requestId = Str::uuid();
        $token = $this->setupAuth($remetente);

        $toAccountId = Str::uuid();
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'X-Request-ID' => $requestId,
        ])->postJson('/api/transacoes/transferir', [
            'from_account_id' => $contaRemetente->public_id,
            'to_account_id' => $toAccountId,
            'amount' => 500,
            'description' => 'Transferência para conta inexistente',
            'transaction_key' => $requestId
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Os dados fornecidos são inválidos'
            ]);

        $this->assertEquals(1000, $contaRemetente->fresh()->balance);
    }

    public function test_deve_realizar_deposito_com_sucesso(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $conta = Conta::factory()->create([
            'user_id' => $usuario->id,
            'balance' => 1000,
            'status' => 'active'
        ]);

        $requestId = Str::uuid();
        $token = $this->setupAuth($usuario);
    
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'X-Request-ID' => $requestId,
        ])->postJson('/api/transacoes/depositar', [
            'to_account_id' => $conta->public_id,
            'amount' => 500,
            'description' => 'Depósito via API',
            'transaction_key' => $requestId
        ]);
    
        // Assert
        $response->assertStatus(202)
            ->assertJson([
                'message' => 'Depósito enfileirado para processamento',
                'amount' => 500,
                'status' => 'pending',
                'transaction_key' => $requestId
            ]);
    }

    public function test_nao_deve_permitir_deposito_com_valor_negativo(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $conta = Conta::factory()->create([
            'user_id' => $usuario->id,
            'balance' => 0,
            'status' => 'active'
        ]);

        $requestId = Str::uuid();
        $token = $this->setupAuth($usuario);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'X-Request-ID' => $requestId,
        ])->postJson('/api/transacoes/depositar', [
            'to_account_id' => $conta->public_id,
            'amount' => -100,
            'description' => 'Tentativa de depósito negativo',
            'transaction_key' => $requestId
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Os dados fornecidos são inválidos'
            ]);

        $this->assertEquals(0, $conta->fresh()->balance);
    }

    public function test_nao_deve_permitir_deposito_em_conta_de_outro_usuario(): void
    {
        // Arrange
        $proprietarioConta = Usuario::factory()->create();
        $conta = Conta::factory()->create([
            'user_id' => $proprietarioConta->id,
            'balance' => 0,
            'status' => 'active'
        ]);

        $outroUsuario = Usuario::factory()->create();
        $requestId = Str::uuid();
        $token = $this->setupAuth($outroUsuario);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'X-Request-ID' => $requestId,
        ])->postJson('/api/transacoes/depositar', [
            'to_account_id' => $conta->public_id,
            'amount' => 1000,
            'description' => 'Tentativa de depósito em conta alheia',
            'transaction_key' => $requestId
        ]);

        // Assert
        $response->assertStatus(403);
        $this->assertEquals(0, $conta->fresh()->balance);
    }

    public function test_deve_processar_deposito_de_forma_idempotente(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $conta = Conta::factory()->create([
            'user_id' => $usuario->id,
            'balance' => 0,
            'status' => 'active'
        ]);

        $requestId = Str::uuid();
        $token = $this->setupAuth($usuario);

        // Primeira requisição
        $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'X-Request-ID' => $requestId,
        ])->postJson('/api/transacoes/depositar', [
            'to_account_id' => $conta->public_id,
            'amount' => 1000,
            'description' => 'Depósito idempotente',
            'transaction_key' => $requestId
        ]);

        // Act - Segunda requisição com mesmo transaction_key
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'X-Request-ID' => $requestId,
        ])->postJson('/api/transacoes/depositar', [
            'to_account_id' => $conta->public_id,
            'amount' => 1000,
            'description' => 'Depósito idempotente',
            'transaction_key' => $requestId
        ]);

        // Assert
        $response->assertStatus(202)
            ->assertJson([
                'message' => 'Depósito enfileirado para processamento',
                'amount' => 1000,
                'status' => 'pending',
                'transaction_key' => $requestId
            ]);

        // Verifica que o saldo foi incrementado apenas uma vez
        $this->assertEquals(1000, $conta->fresh()->balance);
    }

    public function test_deve_realizar_estorno_com_sucesso(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $conta = Conta::factory()->create([
            'user_id' => $usuario->id,
            'balance' => 1000,
            'status' => 'active'
        ]);

        $requestId = Str::uuid();
        $token = $this->setupAuth($usuario);

        // Cria uma transação de depósito para estornar
        $transacao = Transacao::factory()->create([
            'account_id' => $conta->id,
            'type' => 'deposit',
            'amount' => 500.00,
            'status' => 'completed',
            'transaction_key' => $requestId
        ]);
    
        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'X-Request-ID' => $requestId,
        ])->postJson("/api/transacoes/{$transacao->public_id}/estornar", [
            'reason' => 'Estorno solicitado pelo cliente',
            'transaction_key' => $requestId
        ]);
    
        // Assert
        $response->assertStatus(202)
            ->assertJson([
                'message' => 'Estorno enfileirado para processamento',
                'original_transaction_id' => $transacao->public_id,
                'status' => 'pending',
                'transaction_key' => $requestId
            ]);
    
        $this->assertEquals(1000, $conta->fresh()->balance);
    }

    public function test_nao_deve_permitir_estorno_duplicado(): void
    {
        // Arrange
        $remetente = Usuario::factory()->create();
        $contaRemetente = Conta::factory()->create([
            'user_id' => $remetente->id,
            'balance' => 500,
            'status' => 'active'
        ]);

        $destinatario = Usuario::factory()->create();
        $contaDestinatario = Conta::factory()->create([
            'user_id' => $destinatario->id,
            'balance' => 500,
            'status' => 'active'
        ]);

        $transacao = Transacao::factory()->create([
            'account_id' => $contaRemetente->id,
            'type' => 'transfer',
            'amount' => 500,
            'status' => 'pending'
        ]);

        $requestId = Str::uuid();
        $token = $this->setupAuth($remetente);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'X-Request-ID' => $requestId,
        ])->postJson("/api/transacoes/{$transacao->public_id}/estornar", [
            'reason' => 'Tentativa de estorno duplicado',
            'transaction_key' => $requestId
        ]);

        // Assert
        $response->assertStatus(422)
            ->assertJson([
                'message' => 'Esta transação não pode ser estornada'
            ]);

        $this->assertEquals(500, $contaRemetente->fresh()->balance);
        $this->assertEquals(500, $contaDestinatario->fresh()->balance);
    }

    public function test_nao_deve_permitir_estorno_por_usuario_nao_autorizado(): void
    {
        // Arrange
        $remetente = Usuario::factory()->create();
        $contaRemetente = Conta::factory()->create([
            'user_id' => $remetente->id,
            'balance' => 500,
            'status' => 'active'
        ]);

        $destinatario = Usuario::factory()->create();
        $contaDestinatario = Conta::factory()->create([
            'user_id' => $destinatario->id,
            'balance' => 500,
            'status' => 'active'
        ]);

        $requestId = Str::uuid();
        $transacao = Transacao::factory()->create([
            'account_id' => $contaRemetente->id,
            'type' => 'transfer',
            'amount' => 500,
            'status' => 'completed' // status válido para evitar violação de constraint
        ]);

        $usuarioNaoAutorizado = Usuario::factory()->create();
        $token = $this->setupAuth($usuarioNaoAutorizado);

        // Act
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Accept' => 'application/json',
            'X-Request-ID' => $requestId,
        ])->postJson("/api/transacoes/{$transacao->public_id}/estornar", [
            'reason' => 'Tentativa de estorno não autorizado',
            'transaction_key' => $requestId
        ]);

        // Assert
        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Usuário não autorizado para realizar esta operação'
            ]);

        $this->assertEquals(500, $contaRemetente->fresh()->balance);
        $this->assertEquals(500, $contaDestinatario->fresh()->balance);
    }
}
