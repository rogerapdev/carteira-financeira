export interface User {
  id: number;
  nome: string;
  email: string;
  public_id: string;
  created_at: string;
  updated_at: string;
}

export interface AuthResponse {
  token: string;
  token_type: string;
  expires_in: number;
  usuario: User;
}