import { Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-not-found',
  imports: [RouterLink],
  template: `
    <div class="container">
      <h1>404 - Página não encontrada</h1>
      <p>A página que você está procurando não existe ou foi movida.</p>
      <button routerLink="/dashboard">Voltar</button>
    </div>
  `,
  styles: [`
    .container {
      text-align: center;
      padding: 2rem;
    }
    button {
      padding: 0.5rem 1rem;
      background-color: #1976d2;
      color: white;
      border: none;
      border-radius: 4px;
      cursor: pointer;
    }
  `]
})
export class NotFoundComponent {}