import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { Router, RouterLink } from '@angular/router';
import { AccountService, Account, Transaction, PaginatedResponse } from '../../core/services/account.service';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-dashboard',
  standalone: true,
  imports: [CommonModule, RouterLink],
  template: `
    <div class="dashboard-container">
      <div class="header">
        <h1>Carteira Financeira</h1>
        <button class="logout-button" (click)="logout()">
          <span class="logout-icon">↪</span> Sair
        </button>
      </div>
      
      <div class="balance-card">
        <h2>Saldo Atual</h2>
        <p class="balance">R$ {{ account?.balance?.toFixed(2) || '0.00' }}</p>
        <div class="actions">
          <a routerLink="/transfer" class="action-button">
            Transferir
          </a>
          <a routerLink="/deposit" class="action-button">
            Depositar
          </a>
        </div>
      </div>

      <div class="transactions-section">
        <h3>Histórico de Transações</h3>
        <div class="transactions-list">
          @if (transactions.length) {
            @for (transaction of transactions; track transaction.id) {
              <div class="transaction-item" [ngClass]="transaction.type">
                <div class="transaction-info">
                  <span class="transaction-type">
                    {{ getTransactionTypeLabel(transaction.type) }}
                  </span>
                  <span class="transaction-date">
                    {{ transaction.created_at | date:'dd/MM/yyyy HH:mm' }}
                  </span>
                </div>
                <div class="transaction-details">
                  <span class="transaction-amount" [ngClass]="{
                    'negative': transaction.type === 'transfer' || transaction.type === 'reversal',
                    'positive': transaction.type === 'deposit'
                  }">
                    R$ {{ Math.abs(transaction.amount).toFixed(2) }}
                  </span>
                  <span class="transaction-status">
                    {{ getTransactionStatusLabel(transaction.status) }}
                  </span>
                </div>
                <p class="transaction-description">{{ transaction.description }}</p>
                @if (transaction.status === 'completed' && transaction.type !== 'reversal') {
                  <button class="reverse-button" (click)="reverseTransaction(transaction.id)">
                    Estornar
                  </button>
                }
              </div>
            }
            @if (pagination && pagination.total_pages > 1) {
              <div class="pagination">
                <button 
                  [disabled]="currentPage <= 1" 
                  (click)="changePage(currentPage - 1)" 
                  class="pagination-button"
                >
                  Anterior
                </button>
                <span class="page-info">Página {{ currentPage }} de {{ pagination.total_pages }}</span>
                <button 
                  [disabled]="currentPage >= pagination.total_pages" 
                  (click)="changePage(currentPage + 1)" 
                  class="pagination-button"
                >
                  Próxima
                </button>
              </div>
            }
          } @else {
            <p class="no-transactions">Nenhuma transação encontrada.</p>
          }
        </div>
      </div>
    </div>
  `,
  styles: [`
    .dashboard-container {
      max-width: 800px;
      margin: 2rem auto;
      padding: 0 1rem;
    }
    
    .header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 2rem;
    }
    
    .header h1 {
      margin: 0;
      color: #2c3e50;
      font-size: 1.8rem;
    }
    
    .logout-button {
      background-color: #f8f9fa;
      color: #dc3545;
      border: 1px solid #ddd;
      border-radius: 4px;
      padding: 0.5rem 1rem;
      font-size: 0.9rem;
      cursor: pointer;
      display: flex;
      align-items: center;
      transition: all 0.2s;
    }
    
    .logout-button:hover {
      background-color: #dc3545;
      color: white;
      border-color: #dc3545;
    }
    
    .logout-icon {
      margin-right: 0.5rem;
      font-size: 1.1rem;
    }

    .balance-card {
      background-color: #fff;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      text-align: center;
      margin-bottom: 2rem;

      h2 {
        margin: 0;
        color: #666;
      }

      .balance {
        font-size: 2.5rem;
        font-weight: bold;
        margin: 1rem 0;
        color: #2c3e50;
      }
    }

    .actions {
      display: flex;
      gap: 1rem;
      justify-content: center;

      .action-button {
        padding: 0.75rem 1.5rem;
        border-radius: 4px;
        text-decoration: none;
        color: white;
        background-color: #007bff;
        transition: background-color 0.2s;

        &:hover {
          background-color: #0056b3;
        }
      }
    }

    .transactions-section {
      background-color: #fff;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);

      h3 {
        margin: 0 0 1.5rem;
        color: #2c3e50;
      }
    }

    .transaction-item {
      padding: 1rem;
      border-bottom: 1px solid #eee;
      position: relative;

      &:last-child {
        border-bottom: none;
      }

      .transaction-info {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
      }

      .transaction-type {
        font-weight: bold;
        color: #2c3e50;
      }

      .transaction-date {
        color: #666;
      }

      .transaction-details {
        display: flex;
        justify-content: space-between;
        align-items: center;
      }

      .transaction-amount {
        font-weight: bold;

        &.positive {
          color: #28a745;
        }

        &.negative {
          color: #dc3545;
        }
      }

      .transaction-status {
        font-size: 0.875rem;
        padding: 0.25rem 0.5rem;
        border-radius: 4px;
        background-color: #f8f9fa;
        color: #666;
      }

      .transaction-description {
        margin: 0.5rem 0 0;
        color: #666;
        font-size: 0.875rem;
      }

      .reverse-button {
        margin-top: 0.5rem;
        padding: 0.25rem 0.5rem;
        font-size: 0.75rem;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
        color: #dc3545;

        &:hover {
          background-color: #f1f1f1;
        }
      }
    }

    .no-transactions {
      text-align: center;
      color: #666;
      padding: 2rem;
    }

    .pagination {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-top: 1.5rem;
      gap: 1rem;

      .pagination-button {
        padding: 0.5rem 1rem;
        background-color: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;

        &:disabled {
          opacity: 0.5;
          cursor: not-allowed;
        }

        &:hover:not(:disabled) {
          background-color: #e9ecef;
        }
      }

      .page-info {
        color: #666;
      }
    }
  `]
})
export class DashboardComponent implements OnInit {
  private accountService = inject(AccountService);
  private authService = inject(AuthService);
  private router = inject(Router);

  account: Account | null = null;
  transactions: Transaction[] = [];
  accountId: string | null = null;
  accountError: string | null = null;
  transactionsError: string | null = null;
  isLoadingAccount = false;
  isLoadingTransactions = false;
  currentPage = 1;
  pagination: {
    total: number;
    count: number;
    per_page: number;
    current_page: number;
    total_pages: number;
  } | null = null;

  ngOnInit(): void {
    this.loadUserAccount();
    
    // Subscribe to balance updates from the account service
    this.accountService.accountBalance$.subscribe(balance => {
      if (balance !== null && this.account) {
        // Update the balance without reloading the entire account
        this.account.balance = balance;
      }
    });
  }

  private loadUserAccount(): void {
    this.isLoadingAccount = true;
    this.accountService.getUserAccount().subscribe({
      next: (account) => {
        if (account) {
          this.account = account;
          this.accountId = account.id;
          this.loadTransactions();
        } else {
          console.error('No account data available');
          // If we can't get account data, try to reload the user profile
          this.authService.loadUserProfile().subscribe({
            next: (user) => {
              if (user?.conta) {
                this.account = user.conta as Account;
                this.accountId = user.conta.id;
                this.loadTransactions();
              } else {
                this.handleNoAccountData();
              }
            },
            error: (err) => {
              console.error('Error loading user profile:', err);
              this.handleNoAccountData();
            }
          });
        }
        this.isLoadingAccount = false;
      },
      error: (err) => {
        console.error('Error loading account:', err);
        this.isLoadingAccount = false;
        this.handleNoAccountData();
      }
    });
  }

  private handleNoAccountData(): void {
    // Show a message to the user
    alert('Não foi possível carregar os dados da sua conta. Por favor, faça login novamente.');
    // Redirect to login page
    this.authService.logout().subscribe();
  }

  private loadTransactions(): void {
    if (!this.accountId) {
      console.error('Cannot load transactions: No account ID available');
      return;
    }
    
    this.isLoadingTransactions = true;
    this.accountService.getAccountTransactions(this.accountId, this.currentPage).subscribe({
      next: (response) => {
        if (response && response.data) {
          this.transactions = response.data;
          this.pagination = response.meta?.pagination || null;
        } else {
          this.transactions = [];
          console.warn('No transaction data received from API');
        }
        this.isLoadingTransactions = false;
      },
      error: (error) => {
        console.error('Error loading transactions:', error);
        this.transactions = [];
        this.isLoadingTransactions = false;
        // Show a message to the user
        if (error.status === 401) {
          // Unauthorized - token might be invalid
          this.handleNoAccountData();
        }
      }
    });
  }

  protected changePage(page: number): void {
    this.currentPage = page;
    this.loadTransactions();
  }

  protected reverseTransaction(transactionId: string): void {
    if (confirm('Tem certeza que deseja estornar esta transação?')) {
      this.accountService.reverseTransaction(transactionId, { reason: 'Estorno solicitado pelo cliente' })
        .subscribe({
          next: (response) => {
            alert(`Estorno solicitado com sucesso. Status: ${response.status}`);
            this.loadTransactions();
            this.loadUserAccount(); // Reload account to get updated balance
          },
          error: (error) => {
            console.error('Error reversing transaction:', error);
            alert('Erro ao estornar transação. Por favor, tente novamente.');
          }
        });
    }
  }
  
  protected logout(): void {
    if (confirm('Tem certeza que deseja sair?')) {
      this.authService.logout().subscribe({
        next: () => {
          // Navegação é feita dentro do serviço de autenticação
        },
        error: (error) => {
          console.error('Error during logout:', error);
          // Ainda assim, tenta navegar para a página de login
          this.router.navigate(['/auth/login']);
        }
      });
    }
  }

  protected getTransactionTypeLabel(type: string): string {
    const labels = {
      transfer: 'Transferência',
      deposit: 'Depósito',
      reversal: 'Estorno'
    };
    return labels[type as keyof typeof labels] || type;
  }

  protected getTransactionStatusLabel(status: string): string {
    const labels = {
      pending: 'Pendente',
      completed: 'Concluída',
      failed: 'Falha',
      reversed: 'Estornada'
    };
    return labels[status as keyof typeof labels] || status;
  }
}