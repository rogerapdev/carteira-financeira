# Backend da Carteira Financeira

API RESTful desenvolvida com Laravel seguindo os princípios da Clean Architecture.

## Stack Tecnológica

- PHP 8.1+
- Laravel 10.x
- Sanctum para autenticação via API Token
- Fractal para transformação de dados
- MySQL/MariaDB para persistência

## Arquitetura

O projeto segue uma arquitetura limpa (Clean Architecture) com separação clara entre camadas:

### Camadas

- **Domain**: Core do negócio, contendo entidades, value objects e interfaces independentes de frameworks.
- **Application**: Casos de uso da aplicação, orquestrando as regras de negócio.
- **Infrastructure**: Implementações concretas, incluindo persistência e serviços externos.
- **Presentation**: Interface com o mundo externo, incluindo controllers, transformers e requests.

### Princípios Aplicados

- **Dependency Inversion**: Interfaces definem contratos para implementações concretas.
- **Single Responsibility**: Cada classe tem uma única responsabilidade.
- **Open/Closed**: Extensível sem modificar código existente.
- **Interface Segregation**: Interfaces específicas para necessidades específicas.
- **Liskov Substitution**: Implementações podem ser substituídas sem afetar o comportamento.

## Funcionalidades Principais

- Registro e autenticação de usuários
- Gerenciamento de contas financeiras
- Transações (depósitos, transferências)
- Histórico e detalhes de transações
- Estorno de transações

## Como Executar

### Requisitos

- PHP 8.1+
- Composer
- MySQL/MariaDB

### Instalação

1. Instale as dependências:
   ```
   composer install
   ```

2. Configure o arquivo `.env`:
   ```
   cp .env.example .env
   ```
   Edite as variáveis de ambiente conforme necessário, especialmente as relacionadas ao banco de dados.

3. Gere a chave da aplicação:
   ```
   php artisan key:generate
   ```

4. Execute as migrações:
   ```
   php artisan migrate
   ```

5. (Opcional) Popule o banco com dados de exemplo:
   ```
   php artisan db:seed
   ```

6. Inicie o servidor:
   ```
   php artisan serve
   ```

### Configuração de Filas

O sistema utiliza filas para processamento assíncrono de transações:

1. Configure o driver de filas no `.env` (recomendado: redis):
   ```
   QUEUE_CONNECTION=redis
   ```

2. Execute o worker:
   ```
   php artisan queue:work
   ```

3. Para produção, configure um supervisor para manter o worker ativo.

## API Endpoints

### Autenticação

- `POST /api/register` - Registrar novo usuário
- `POST /api/login` - Autenticar usuário e receber token
- `GET /api/me` - Obter informações do usuário autenticado
- `POST /api/logout` - Invalidar token atual

### Conta

- `GET /api/account` - Obter detalhes da conta
- `GET /api/account/balance` - Consultar saldo

### Transações

- `GET /api/transactions` - Listar histórico de transações
- `GET /api/transactions/{id}` - Obter detalhes de uma transação
- `POST /api/transactions/transfer` - Realizar transferência
- `POST /api/transactions/deposit` - Realizar depósito
- `POST /api/transactions/{id}/reverse` - Estornar transação

## Recursos Avançados

### Geração de Relatórios

O sistema possui um comando para gerar relatórios de transações:

```
php artisan report:transactions --date=2023-07-01
```

Isso gerará um relatório CSV em `storage/app/reports/`.

### Agendamento

Tarefas agendadas estão configuradas em `app/Console/Kernel.php`:

- Geração diária de relatórios
- Manutenção de filas
- Limpeza de tokens expirados

## Testes

Execute os testes com:

```
php artisan test
```
