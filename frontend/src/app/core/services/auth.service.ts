import { Injectable, signal } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Router } from '@angular/router';
import { Observable, tap, map, catchError, throwError } from 'rxjs';
import { CookieService } from './cookie.service';

export interface User {
  id: string;
  name: string;
  email: string;
  document: string;
  phone: string;
  status?: string;
  created_at?: string;
  updated_at?: string;
  conta?: {
    id: string;
    user_id: string;
    balance: number;
    status: string;
    created_at: string;
    updated_at: string;
  };
}

export interface ApiResponse<T> {
  data: T;
}

export interface AuthResponse extends User {
  token: string;
}

@Injectable({
  providedIn: 'root'
})
export class AuthService {
  private readonly API_URL = 'http://localhost:8001/api';
  private readonly TOKEN_KEY = '@carteira:token';
  private readonly USER_KEY = '@carteira:user';

  private userSignal = signal<User | null>(null);

  constructor(
    private http: HttpClient,
    private router: Router,
    private cookieService: CookieService
  ) {
    this.loadStoredAuth();
  }

  private loadStoredAuth(): void {
    // Try to get from secure storage first (most secure)
    let storedToken = this.cookieService.getSecureStorage(this.TOKEN_KEY);
    let storedUser = this.cookieService.getSecureStorage(this.USER_KEY);

    // If not found in secure storage, try localStorage as fallback
    if (!storedToken || !storedUser) {
      storedToken = this.cookieService.getLocalStorage(this.TOKEN_KEY);
      storedUser = this.cookieService.getLocalStorage(this.USER_KEY);
      
      // If found in localStorage, migrate to secure storage
      if (storedToken && storedUser) {
        this.cookieService.setSecureStorage(this.TOKEN_KEY, storedToken);
        this.cookieService.setSecureStorage(this.USER_KEY, storedUser);
        
        // Remove from less secure storage
        this.cookieService.removeLocalStorage(this.TOKEN_KEY);
        this.cookieService.removeLocalStorage(this.USER_KEY);
        this.cookieService.deleteCookie(this.TOKEN_KEY);
        this.cookieService.deleteCookie(this.USER_KEY);
      }
    }

    if (storedToken && storedUser) {
      try {
        const userData = JSON.parse(storedUser);
        this.userSignal.set(userData);
        this.loadUserProfile().subscribe({
          next: (updatedUser) => {
            // Update stored user data with latest from server
            this.cookieService.setSecureStorage(this.USER_KEY, JSON.stringify(updatedUser));
          },
          error: (err) => {
            console.error('Error loading user profile:', err);
            // If profile loading fails, try to use the stored data
            if (userData) {
              this.userSignal.set(userData);
            }
          }
        });
      } catch (e) {
        console.error('Error parsing stored user data:', e);
        // Clear invalid data
        this.clearAllStoredAuth();
      }
    }
  }
  
  private clearAllStoredAuth(): void {
    this.cookieService.removeSecureStorage(this.TOKEN_KEY);
    this.cookieService.removeSecureStorage(this.USER_KEY);
    this.cookieService.removeLocalStorage(this.TOKEN_KEY);
    this.cookieService.removeLocalStorage(this.USER_KEY);
    this.cookieService.deleteCookie(this.TOKEN_KEY);
    this.cookieService.deleteCookie(this.USER_KEY);
  }

  loadUserProfile(): Observable<User> {
    return this.http.get<ApiResponse<User>>(`${this.API_URL}/perfil`)
      .pipe(
        map(response => response.data),
        tap(user => {
          this.cookieService.setCookie(this.USER_KEY, JSON.stringify(user));
          this.userSignal.set(user);
        })
      );
  }

  login(email: string, password: string): Observable<AuthResponse> {
    return this.http.post<ApiResponse<AuthResponse>>(`${this.API_URL}/login`, { email, password })
      .pipe(
        map(response => response.data),
        tap(userData => {
          // Store only in secure storage
          this.cookieService.setSecureStorage(this.TOKEN_KEY, userData.token);
          this.cookieService.setSecureStorage(this.USER_KEY, JSON.stringify(userData));
          
          // Set user data in the signal
          this.userSignal.set(userData);
          
          // Remove any old data from less secure storage
          this.cookieService.deleteCookie(this.TOKEN_KEY);
          this.cookieService.deleteCookie(this.USER_KEY);
          this.cookieService.removeLocalStorage(this.TOKEN_KEY);
          this.cookieService.removeLocalStorage(this.USER_KEY);
        })
      );
  }

  register(userData: Omit<User, 'id'> & { password: string; password_confirmation: string }): Observable<User> {
    return this.http.post<ApiResponse<User>>(`${this.API_URL}/cadastrar`, userData)
      .pipe(
        map(response => response.data),
        tap(user => {
          this.userSignal.set(user);
        })
      );
  }

  logout(): Observable<any> {
    return this.http.post<{mensagem: string}>(`${this.API_URL}/logout`, {})
      .pipe(
        tap(() => {
          // Clear all storage methods
          this.clearAllStoredAuth();
          this.userSignal.set(null);
          this.router.navigate(['/auth/login']);
        }),
        // Handle error case - still clear local data even if API call fails
        catchError(error => {
          this.clearAllStoredAuth();
          this.userSignal.set(null);
          this.router.navigate(['/auth/login']);
          return throwError(() => error);
        })
      );
  }

  getToken(): string | null {
    // Try to get token from secure storage first
    let token = this.cookieService.getSecureStorage(this.TOKEN_KEY);
    
    // If not found, try fallback methods
    if (!token) {
      token = this.cookieService.getLocalStorage(this.TOKEN_KEY);
      
      // If found in localStorage, migrate to secure storage
      if (token) {
        this.cookieService.setSecureStorage(this.TOKEN_KEY, token);
        this.cookieService.removeLocalStorage(this.TOKEN_KEY);
      } else {
        // Last resort, try cookies
        token = this.cookieService.getCookie(this.TOKEN_KEY);
        if (token) {
          this.cookieService.setSecureStorage(this.TOKEN_KEY, token);
          this.cookieService.deleteCookie(this.TOKEN_KEY);
        }
      }
    }
    
    return token;
  }

  isAuthenticated(): boolean {
    return !!this.getToken();
  }

  getUser(): User | null {
    return this.userSignal();
  }
}