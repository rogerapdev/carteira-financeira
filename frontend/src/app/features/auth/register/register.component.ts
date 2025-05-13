import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthService } from '../../../core/services/auth.service';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  template: `
    <div class="register-container">
      <h2>Cadastro</h2>
      <form [formGroup]="registerForm" (ngSubmit)="onSubmit()">
        <div class="form-group">
          <label for="name">Nome Completo</label>
          <input
            type="text"
            id="name"
            formControlName="name"
            placeholder="Seu nome completo"
            required
          />
        </div>

        <div class="form-group">
          <label for="email">E-mail</label>
          <input
            type="email"
            id="email"
            formControlName="email"
            placeholder="Seu e-mail"
            required
          />
        </div>

        <div class="form-group">
          <label for="document">CPF/CNPJ</label>
          <input
            type="text"
            id="document"
            formControlName="document"
            placeholder="Seu CPF ou CNPJ"
            required
          />
        </div>

        <div class="form-group">
          <label for="phone">Telefone</label>
          <input
            type="tel"
            id="phone"
            formControlName="phone"
            placeholder="Seu telefone"
            required
          />
        </div>

        <div class="form-group">
          <label for="password">Senha</label>
          <input
            type="password"
            id="password"
            formControlName="password"
            placeholder="Sua senha"
            required
          />
        </div>

        <div class="form-group">
          <label for="passwordConfirmation">Confirmar Senha</label>
          <input
            type="password"
            id="passwordConfirmation"
            formControlName="passwordConfirmation"
            placeholder="Confirme sua senha"
            required
          />
        </div>

        <button type="submit" [disabled]="registerForm.invalid || isLoading">
          {{ isLoading ? 'Cadastrando...' : 'Cadastrar' }}
        </button>

        <p class="error" *ngIf="error">{{ error }}</p>

        <p class="login-link">
          Já tem uma conta? <a routerLink="/auth/login">Faça login</a>
        </p>
      </form>
    </div>
  `,
  styles: [`
    .register-container {
      max-width: 400px;
      margin: 2rem auto;
      padding: 2rem;
      border-radius: 8px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    h2 {
      text-align: center;
      margin-bottom: 2rem;
    }

    .form-group {
      margin-bottom: 1rem;
    }

    label {
      display: block;
      margin-bottom: 0.5rem;
    }

    input {
      width: 100%;
      padding: 0.5rem;
      border: 1px solid #ddd;
      border-radius: 4px;
    }

    button {
      width: 100%;
      padding: 0.75rem;
      background-color: #007bff;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      margin-top: 1rem;

      &:disabled {
        background-color: #ccc;
        cursor: not-allowed;
      }
    }

    .error {
      color: red;
      text-align: center;
      margin-top: 1rem;
    }

    .login-link {
      text-align: center;
      margin-top: 1rem;

      a {
        color: #007bff;
        text-decoration: none;

        &:hover {
          text-decoration: underline;
        }
      }
    }
  `]
})
export class RegisterComponent {
  private fb = inject(FormBuilder);
  private authService = inject(AuthService);
  private router = inject(Router);

  registerForm: FormGroup = this.fb.group({
    name: ['', [Validators.required]],
    email: ['', [Validators.required, Validators.email]],
    document: ['', [Validators.required]],
    phone: ['', [Validators.required]],
    password: ['', [Validators.required, Validators.minLength(6)]],
    passwordConfirmation: ['', [Validators.required]]
  }, {
    validators: this.passwordMatchValidator
  });

  isLoading = false;
  error = '';

  private passwordMatchValidator(g: FormGroup) {
    return g.get('password')?.value === g.get('passwordConfirmation')?.value
      ? null
      : { mismatch: true };
  }

  onSubmit(): void {
    if (this.registerForm.valid) {
      this.isLoading = true;
      this.error = '';

      const { passwordConfirmation, ...userData } = this.registerForm.value;

      this.authService.register(userData).subscribe({
        next: () => {
          this.router.navigate(['/dashboard']);
        },
        error: (err) => {
          this.error = 'Erro ao cadastrar. Por favor, verifique os dados e tente novamente.';
          this.isLoading = false;
        }
      });
    }
  }
}