<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Domain\Entities\Usuario;
use App\Domain\Entities\Conta;
use App\Domain\Entities\Transacao;
use App\Application\Services\TransacaoService;
use App\Application\DTOs\TransacaoDTO;
use App\Domain\Exceptions\SaldoInsuficienteException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

class TransacaoServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransacaoService $transacaoService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transacaoService = app(TransacaoService::class);
    }

    public function test_deve_transferir_dinheiro_entre_contas(): void
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

        // Cria o DTO para a transferência
        $transacaoDTO = new TransacaoDTO(
            id: null,
            account_id: $contaRemetente->id,
            type: 'transfer',
            amount: 500,
            reference_id: null,
            status: 'pending',
            description: 'Transferência de teste',
            from_account_id: $contaRemetente->public_id,
            to_account_id: $contaDestinatario->public_id,
            transaction_key: Str::uuid()
        );

        // Act
        $resultado = $this->transacaoService->criarTransferencia($transacaoDTO);

        // Assert
        $this->assertInstanceOf(Transacao::class, $resultado);
        $this->assertEquals('completed', $resultado->status);
        $this->assertEquals(500, $contaRemetente->fresh()->balance);
        $this->assertEquals(500, $contaDestinatario->fresh()->balance);
    }

    public function test_deve_realizar_deposito(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $conta = Conta::factory()->create([
            'user_id' => $usuario->id,
            'balance' => 1000,
            'status' => 'active'
        ]);

        // Cria o DTO para o depósito
        $transacaoDTO = new TransacaoDTO(
            id: null,
            account_id: $conta->id,
            type: 'deposit',
            amount: 500,
            reference_id: null,
            status: 'pending',
            description: 'Depósito de teste',
            to_account_id: $conta->public_id,
            transaction_key: Str::uuid()
        );

        // Act
        $resultado = $this->transacaoService->criarDeposito($transacaoDTO);

        // Assert
        $this->assertInstanceOf(Transacao::class, $resultado);
        $this->assertEquals('completed', $resultado->status);
        $this->assertEquals(1500, $conta->fresh()->balance); // ... existing code ...
    }

    public function test_deve_realizar_estorno(): void
    {
        // Arrange
        $usuario = Usuario::factory()->create();
        $conta = Conta::factory()->create([
            'user_id' => $usuario->id,
            'balance' => 1000,
            'status' => 'active'
        ]);
    
        $requestId = Str::uuid();
        // Cria o DTO para o estorno
        $transacaoDTO = new TransacaoDTO(
            id: null,
            account_id: $conta->id,
            type: 'refund',
            amount: 500,
            reference_id: null,
            status: 'pending',
            description: 'Estorno de teste',
            to_account_id: $conta->public_id,
            transaction_key: $requestId
        );

        // Act
        $resultadoDeposito = $this->transacaoService->criarDeposito($transacaoDTO);

        // Simula o estorno
        $resultado = $this->transacaoService->estornarTransacao($resultadoDeposito->id, 'Estorno de teste'); 

        // Assert
        $this->assertInstanceOf(Transacao::class, $resultado);
        $this->assertEquals('completed', $resultado->status);
        $this->assertEquals(1000, $conta->fresh()->balance);
    }

    public function test_nao_deve_permitir_transferencia_com_saldo_insuficiente(): void
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

        // Assert & Act
        $this->expectException(SaldoInsuficienteException::class);

        $transacaoDTO = new TransacaoDTO(
            id: null,
            account_id: $contaRemetente->id,
            type: 'transfer',
            amount: 500,
            reference_id: null,
            status: 'pending',
            description: 'Transferência que deve falhar',
            from_account_id: $contaRemetente->public_id,
            to_account_id: $contaDestinatario->public_id,
            transaction_key: Str::uuid()
        );
        
        $this->transacaoService->criarTransferencia($transacaoDTO);
    }
}