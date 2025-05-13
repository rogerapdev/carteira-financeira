<?php

namespace App\Console\Commands;

use App\Domain\Entities\Transacao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class GerarRelatorioTransacoes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'relatorio:transacoes {--data= : Data para o relatório (AAAA-MM-DD)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gera um relatório de transações para uma data específica';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Obtém a data das opções ou usa o dia atual
        $data = $this->option('data') ? $this->option('data') : now()->format('Y-m-d');
        
        $this->info("Gerando relatório de transações para {$data}");

        // Consulta transações para a data especificada
        $transacoes = Transacao::query()
            ->whereDate('created_at', $data)
            ->get();

        if ($transacoes->isEmpty()) {
            $this->warn("Nenhuma transação encontrada para {$data}");
            return 0;
        }
        
        // Obtém estatísticas resumidas
        $valorTotal = $transacoes->sum('amount');
        $quantidadeTotal = $transacoes->count();
        $valorMedio = $valorTotal / $quantidadeTotal;
        
        $quantidadeDepositos = $transacoes->where('type', Transacao::TIPO_DEPOSITO)->count();
        $quantidadeTransferencias = $transacoes->where('type', Transacao::TIPO_TRANSFERENCIA)->count();
        $quantidadeEstornos = $transacoes->where('type', Transacao::TIPO_ESTORNO)->count();
        
        $valorDepositos = $transacoes->where('type', Transacao::TIPO_DEPOSITO)->sum('amount');
        $valorTransferencias = $transacoes->where('type', Transacao::TIPO_TRANSFERENCIA)->sum('amount');
        $valorEstornos = $transacoes->where('type', Transacao::TIPO_ESTORNO)->sum('amount');

        // Gera relatório CSV
        $nomeArquivo = "transacoes-{$data}.csv";
        $cabecalhos = [
            'ID', 'ID da Conta', 'Tipo', 'Valor', 'Status', 'ID de Referência', 'Descrição', 'Data de Criação'
        ];
        
        $dadosCSV = [];
        $dadosCSV[] = implode(',', $cabecalhos);
        
        foreach ($transacoes as $transacao) {
            $dadosCSV[] = implode(',', [
                $transacao->id,
                $transacao->account_id,
                $transacao->type,
                $transacao->amount,
                $transacao->status,
                $transacao->reference_id ?? 'N/A',
                '"' . str_replace('"', '""', $transacao->description ?? '') . '"',
                $transacao->created_at
            ]);
        }
        
        // Adiciona resumo no final
        $dadosCSV[] = '';
        $dadosCSV[] = 'Resumo';
        $dadosCSV[] = "Total de Transações,{$quantidadeTotal}";
        $dadosCSV[] = "Valor Total,{$valorTotal}";
        $dadosCSV[] = "Valor Médio,{$valorMedio}";
        $dadosCSV[] = '';
        $dadosCSV[] = 'Por Tipo,Quantidade,Valor';
        $dadosCSV[] = "Depósitos,{$quantidadeDepositos},{$valorDepositos}";
        $dadosCSV[] = "Transferências,{$quantidadeTransferencias},{$valorTransferencias}";
        $dadosCSV[] = "Estornos,{$quantidadeEstornos},{$valorEstornos}";
        
        // Escreve no armazenamento
        Storage::disk('local')->put("relatorios/{$nomeArquivo}", implode("\n", $dadosCSV));
        
        $this->info("Relatório gerado com sucesso: storage/app/relatorios/{$nomeArquivo}");
        
        // Exibe resumo no console
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Total de Transações', $quantidadeTotal],
                ['Valor Total', $valorTotal],
                ['Valor Médio', round($valorMedio, 2)],
                ['Depósitos', "{$quantidadeDepositos} ({$valorDepositos})"],
                ['Transferências', "{$quantidadeTransferencias} ({$valorTransferencias})"],
                ['Estornos', "{$quantidadeEstornos} ({$valorEstornos})"],
            ]
        );
        
        return 0;
    }
} 