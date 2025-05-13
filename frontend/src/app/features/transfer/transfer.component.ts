import { Component, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router } from '@angular/router';
import { AccountService } from '../../core/services/account.service';
import { AuthService } from '../../core/services/auth.service';

@Component({
  selector: 'app-transfer',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule],
  template: `
    <div class="transfer-container">
      <h2>Realizar Transferência</h2>
      <form [formGroup]="transferForm" (ngSubmit)="onSubmit()">
        <div class="form-group">
          <label for="toAccountId">Conta de Destino (ID)</label>
          <input
            type="text"
            id="toAccountId"
            formControlName="toAccountId"
            placeholder="ID da conta de destino"
            required
          />
        </div>

        <div class="form-group">
          <label for="amount">Valor</label>
          <input
            type="number"
            id="amount"
            formControlName="amount"
            placeholder="Valor a transferir"
            step="0.01"
            min="0.01"
            required
          />
        </div>

        <div class="form-group">
          <label for="description">Descrição</label>
          <input
            type="text"
            id="description"
            formControlName="description"
            placeholder="Descrição da transferência"
            required
          />
        </div>

        <button type="submit" [disabled]="transferForm.invalid || isLoading">
          {{ isLoading ? 'Transferindo...' : 'Transferir' }}
        </button>

        <button type="button" class="secondary" (click)="goBack()">
          Voltar
        </button>

        <p class="error" *ngIf="error">{{ error }}</p>
        <p class="success" *ngIf="success">{{ success }}</p>
      </form>
    </div>
  `,
  styles: [`
    .transfer-container {
      max-width: 400px;
      margin: 2rem auto;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      background-color: white;
    }

    h2 {
      text-align: center;
      margin-bottom: 2rem;
      color: #2c3e50;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    label {
      display: block;
      margin-bottom: 0.5rem;
      color: #2c3e50;
    }

    input {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 1rem;

      &:focus {
        outline: none;
        border-color: #007bff;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
      }
    }

    button {
      width: 100%;
      padding: 0.75rem;
      border: none;
      border-radius: 4px;
      font-size: 1rem;
      cursor: pointer;
      margin-top: 1rem;

      &:not(.secondary) {
        background-color: #007bff;
        color: white;

        &:hover:not(:disabled) {
          background-color: #0056b3;
        }

        &:disabled {
          background-color: #ccc;
          cursor: not-allowed;
        }
      }

      &.secondary {
        background-color: #f8f9fa;
        color: #2c3e50;
        border: 1px solid #ddd;

        &:hover {
          background-color: #e2e6ea;
        }
      }
    }

    .error {
      color: #dc3545;
      text-align: center;
      margin-top: 1rem;
    }
    
    .success {
      color: #28a745;
      text-align: center;
      margin-top: 1rem;
    }
  `]
})
export class TransferComponent implements OnInit {
  private fb = inject(FormBuilder);
  private accountService = inject(AccountService);
  private authService = inject(AuthService);
  private router = inject(Router);

  accountId: string | null = null;
  
  transferForm: FormGroup = this.fb.group({
    toAccountId: ['', [Validators.required]],
    amount: ['', [Validators.required, Validators.min(0.01)]],
    description: ['', [Validators.required, Validators.maxLength(255)]]
  });

  isLoading = false;
  error = '';
  success = '';

  ngOnInit(): void {
    this.loadUserAccount();
  }

  private loadUserAccount(): void {
    this.accountService.getUserAccount().subscribe(account => {
      if (account) {
        this.accountId = account.id;
      } else {
        this.error = 'Não foi possível carregar os dados da conta.';
      }
    });
  }

  onSubmit(): void {
    if (this.transferForm.valid && this.accountId) {
      this.isLoading = true;
      this.error = '';
      this.success = '';

      const transferData = {
        from_account_id: this.accountId,
        to_account_id: this.transferForm.value.toAccountId,
        amount: this.transferForm.value.amount,
        description: this.transferForm.value.description,
        transaction_key: crypto.randomUUID()
      };

      this.accountService.createTransfer(transferData).subscribe({
        next: (response) => {
          this.success = `Transferência de R$ ${transferData.amount.toFixed(2)} enfileirada para processamento.`;
          this.isLoading = false;
          this.transferForm.reset();
          
          // Redirect to dashboard after a short delay to show success message
          setTimeout(() => {
            this.router.navigate(['/dashboard']);
          }, 2000);
        },
        error: (err) => {
          console.error('Error making transfer:', err);
          this.error = 'Erro ao realizar transferência. Por favor, verifique os dados e tente novamente.';
          this.isLoading = false;
        }
      });
    }
  }

  goBack(): void {
    this.router.navigate(['/dashboard']);
  }
}