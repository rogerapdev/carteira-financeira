import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable, map, catchError, of, BehaviorSubject } from 'rxjs';
import { AuthService } from './auth.service';

export interface Account {
  id: string;
  user_id: string;
  balance: number;
  status: string;
  created_at: string;
  updated_at: string;
}

export interface Transaction {
  id: string;
  account_id: string;
  type: 'deposit' | 'transfer' | 'reversal';
  amount: number;
  reference_id?: string;
  status: 'pending' | 'completed' | 'failed' | 'reversed';
  description: string;
  error_message?: string;
  created_at: string;
  updated_at: string;
}

export interface TransferRequest {
  from_account_id: string;
  to_account_id: string;
  amount: number;
  description: string;
  transaction_key?: string;
}

export interface DepositRequest {
  to_account_id: string;
  amount: number;
  description: string;
  transaction_key?: string;
}

export interface ReversalRequest {
  reason: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    pagination: {
      total: number;
      count: number;
      per_page: number;
      current_page: number;
      total_pages: number;
    }
  }
}

export interface ApiResponse<T> {
  data: T;
}

export interface TransactionResponse {
  message: string;
  amount: number;
  status: string;
  transaction_key: string;
  from_account_id?: string;
  to_account_id?: string;
  original_transaction_id?: string;
  original_transaction_type?: string;
  request_date?: string;
}

@Injectable({
  providedIn: 'root'
})
export class AccountService {
  private readonly API_URL = 'http://localhost:8001/api';
  
  // BehaviorSubject to track account balance changes
  private accountBalanceSubject = new BehaviorSubject<number | null>(null);
  public accountBalance$ = this.accountBalanceSubject.asObservable();

  constructor(
    private http: HttpClient,
    private authService: AuthService
  ) {}

  getAccountDetails(accountId: string): Observable<Account> {
    return this.http.get<ApiResponse<Account>>(`${this.API_URL}/contas/${accountId}`)
      .pipe(map(response => response.data));
  }

  getUserAccount(): Observable<Account | null> {
    const user = this.authService.getUser();
    
    if (user?.conta) {
      // Use of legacy Observable.create was causing issues
      return new Observable<Account | null>(observer => {
        // Ensure we're passing a valid Account object
        if (this.isValidAccount(user.conta)) {
          const account = user.conta as Account;
          // Update the balance subject
          this.updateBalanceSubject(account.balance);
          observer.next(account);
        } else {
          console.warn('Invalid account data in user object:', user.conta);
          observer.next(null);
        }
        observer.complete();
      });
    } else {
      // If user exists but conta is missing, or user is null, try to load profile
      return this.authService.loadUserProfile().pipe(
        map(user => {
          if (user?.conta && this.isValidAccount(user.conta)) {
            const account = user.conta as Account;
            // Update the balance subject
            this.updateBalanceSubject(account.balance);
            return account;
          }
          return null;
        }),
        catchError(error => {
          console.error('Error loading user profile for account data:', error);
          return of(null);
        })
      );
    }
  }

  // Helper method to validate account data
  private isValidAccount(account: any): account is Account {
    return account && 
           typeof account === 'object' &&
           'id' in account &&
           'user_id' in account &&
           'balance' in account &&
           'status' in account;
  }

  depositDirectToAccount(accountId: string, amount: number): Observable<Account> {
    return this.http.post<ApiResponse<Account>>(`${this.API_URL}/contas/${accountId}/depositar`, { amount })
      .pipe(map(response => response.data));
  }

  withdrawFromAccount(accountId: string, amount: number): Observable<Account> {
    return this.http.post<ApiResponse<Account>>(`${this.API_URL}/contas/${accountId}/sacar`, { amount })
      .pipe(map(response => response.data));
  }

  getAccountTransactions(accountId: string, page: number = 1): Observable<PaginatedResponse<Transaction>> {
    return this.http.get<PaginatedResponse<Transaction>>(
      `${this.API_URL}/contas/${accountId}/transacoes?page=${page}`
    );
  }

  getTransactionDetails(transactionId: string): Observable<Transaction> {
    return this.http.get<ApiResponse<Transaction>>(`${this.API_URL}/transacoes/${transactionId}`)
      .pipe(map(response => response.data));
  }

  createDeposit(data: DepositRequest): Observable<TransactionResponse> {
    // Generate a UUID for transaction_key if not provided
    if (!data.transaction_key) {
      data.transaction_key = crypto.randomUUID();
    }
    return this.http.post<TransactionResponse>(`${this.API_URL}/transacoes/depositar`, data).pipe(
      map(response => {
        // After successful deposit, refresh the user account to get updated balance
        this.refreshUserAccount();
        return response;
      })
    );
  }

  createTransfer(data: TransferRequest): Observable<TransactionResponse> {
    // Generate a UUID for transaction_key if not provided
    if (!data.transaction_key) {
      data.transaction_key = crypto.randomUUID();
    }
    return this.http.post<TransactionResponse>(`${this.API_URL}/transacoes/transferir`, data).pipe(
      map(response => {
        // After successful transfer, refresh the user account to get updated balance
        this.refreshUserAccount();
        return response;
      })
    );
  }

  reverseTransaction(transactionId: string, data: ReversalRequest): Observable<TransactionResponse> {
    return this.http.post<TransactionResponse>(`${this.API_URL}/transacoes/${transactionId}/estornar`, data).pipe(
      map(response => {
        // After successful reversal, refresh the user account to get updated balance
        this.refreshUserAccount();
        return response;
      })
    );
  }
  
  /**
   * Updates the balance subject with a new value
   */
  private updateBalanceSubject(balance: number | null): void {
    this.accountBalanceSubject.next(balance);
  }
  
  /**
   * Refreshes the user account data to get the latest balance
   */
  public refreshUserAccount(): void {
    // Get the latest user profile from the server
    this.authService.loadUserProfile().pipe(
      map(user => {
        if (user?.conta && this.isValidAccount(user.conta)) {
          const account = user.conta as Account;
          // Update the balance subject with the new balance
          this.updateBalanceSubject(account.balance);
          return account;
        }
        return null;
      }),
      catchError(error => {
        console.error('Error refreshing user account data:', error);
        return of(null);
      })
    ).subscribe();
  }
}