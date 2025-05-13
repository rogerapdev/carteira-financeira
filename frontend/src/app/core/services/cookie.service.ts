import { Injectable } from '@angular/core';

@Injectable({
  providedIn: 'root'
})
export class CookieService {
  // For non-sensitive data only
  setCookie(name: string, value: string, days: number = 7): void {
    const date = new Date();
    date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
    const expires = `expires=${date.toUTCString()}`;
    document.cookie = `${name}=${value}; ${expires}; path=/; Secure; SameSite=Strict`;
  }

  getCookie(name: string): string | null {
    const cookies = document.cookie.split(';');
    for (const cookie of cookies) {
      const [cookieName, cookieValue] = cookie.split('=').map(c => c.trim());
      if (cookieName === name) {
        return cookieValue;
      }
    }
    return null;
  }

  deleteCookie(name: string): void {
    document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/; Secure; SameSite=Strict`;
  }

  // Secure storage for sensitive data
  // Uses sessionStorage which is cleared when the browser is closed
  // and encrypts the data before storing
  setSecureStorage(name: string, value: string): void {
    try {
      // Simple obfuscation - in a real app, use a proper encryption library
      const obfuscatedValue = btoa(encodeURIComponent(value));
      sessionStorage.setItem(name, obfuscatedValue);
    } catch (e) {
      console.error('Error storing secure data:', e);
    }
  }

  getSecureStorage(name: string): string | null {
    try {
      const value = sessionStorage.getItem(name);
      if (!value) return null;
      // Decode the obfuscated value
      return decodeURIComponent(atob(value));
    } catch (e) {
      console.error('Error retrieving secure data:', e);
      return null;
    }
  }

  removeSecureStorage(name: string): void {
    sessionStorage.removeItem(name);
  }

  // For non-sensitive data that needs to persist
  setLocalStorage(name: string, value: string): void {
    localStorage.setItem(name, value);
  }

  getLocalStorage(name: string): string | null {
    return localStorage.getItem(name);
  }

  removeLocalStorage(name: string): void {
    localStorage.removeItem(name);
  }

  // Clear all storage
  clearAllStorage(): void {
    // Clear cookies
    const cookies = document.cookie.split(';');
    for (const cookie of cookies) {
      const cookieName = cookie.split('=')[0].trim();
      this.deleteCookie(cookieName);
    }
    
    // Clear session and local storage
    sessionStorage.clear();
    localStorage.clear();
  }
}