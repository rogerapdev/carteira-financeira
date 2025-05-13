# Carteira Financeira

Uma aplicação completa de carteira financeira digital desenvolvida com Laravel (backend) e Angular (frontend), utilizando arquitetura limpa e práticas modernas de desenvolvimento.

## 📋 Visão Geral

Carteira Financeira é um sistema completo que permite aos usuários gerenciar suas finanças digitais através de uma interface intuitiva. O projeto foi desenvolvido seguindo princípios de Clean Architecture, Domain-Driven Design (DDD) e Test-Driven Development (TDD), demonstrando conhecimento avançado em desenvolvimento de software.

### 🚀 Principais Funcionalidades

- **Autenticação Segura**: Cadastro, login e gerenciamento de perfil de usuários
- **Gerenciamento de Contas**: Visualização de saldo e detalhes da conta
- **Transações Financeiras**: 
  - Depósitos
  - Transferências entre contas
  - Estornos de transações
- **Histórico de Transações**: Visualização completa do histórico financeiro
- **Monitoramento em Tempo Real**: Dashboard com métricas e atualizações instantâneas

## 🛠️ Tecnologias Utilizadas

### Backend
- **PHP 8.1+**: Linguagem base do backend
- **Laravel 10**: Framework PHP para desenvolvimento rápido e seguro
- **PostgreSQL**: Banco de dados relacional para armazenamento persistente
- **Redis**: Armazenamento em cache e filas de processamento
- **Laravel Horizon**: Monitoramento e gerenciamento de filas
- **Swagger/OpenAPI**: Documentação automática da API
- **PHPUnit**: Testes automatizados

### Frontend
- **TypeScript**: Linguagem fortemente tipada para desenvolvimento frontend
- **Angular 19**: Framework para construção de interfaces modernas e reativas
- **RxJS**: Biblioteca para programação reativa
- **HTML5/CSS3**: Estruturação e estilização da interface

### DevOps & Infraestrutura
- **Docker & Docker Compose**: Containerização e orquestração de serviços
- **Nginx**: Servidor web de alta performance
- **Prometheus & Grafana**: Monitoramento e visualização de métricas
- **Node Exporter & cAdvisor**: Coleta de métricas do sistema e containers
- **AlertManager**: Gestão de alertas baseados em métricas

### Práticas de Desenvolvimento
- **Clean Architecture**: Separação clara de responsabilidades
- **Domain-Driven Design (DDD)**: Modelagem orientada ao domínio
- **Test-Driven Development (TDD)**: Desenvolvimento guiado por testes
- **Controle de Versão**: Git para versionamento de código

## 🏗️ Arquitetura

O projeto segue uma arquitetura moderna e escalável:

### Backend (Laravel)
- **Domain Layer**: Entidades de negócio e regras de domínio
- **Application Layer**: Casos de uso e orquestração de serviços
- **Infrastructure Layer**: Implementações concretas (repositórios, serviços externos)
- **Presentation Layer**: Controllers, transformers e serializers para API

### Frontend (Angular)
- **Core Module**: Serviços compartilhados, interceptors e guards
- **Feature Modules**: Componentes específicos de cada funcionalidade
- **Shared Module**: Componentes, pipes e diretivas reutilizáveis
- **State Management**: Gerenciamento reativo de estado com RxJS

## 📊 Monitoramento e Observabilidade

O sistema inclui uma stack completa de monitoramento:

- **Prometheus**: Coleta e armazenamento de métricas
- **Grafana**: Dashboards visuais para análise de performance
- **AlertManager**: Configuração de alertas baseados em thresholds
- **Node Exporter & cAdvisor**: Métricas do host e containers

## 🚀 Instalação e Execução

### Pré-requisitos
- Docker e Docker Compose instalados
- Git

### Passos para Instalação

1. Clone o repositório:
   ```bash
   git clone https://github.com/seu-usuario/carteira-financeira.git
   cd carteira-financeira
   ```

2. Inicie os containers com Docker Compose:
   ```bash
   docker-compose up -d
   ```

3. O sistema estará disponível nos seguintes endereços:
   - **Frontend**: http://localhost:8000
   - **Backend API**: http://localhost:8001
   - **Grafana**: http://localhost:3000 (usuário: admin, senha: admin)
   - **Prometheus**: http://localhost:9090

### Configuração Inicial

O sistema já vem pré-configurado para desenvolvimento, com:
- Migrations e seeds automáticos para o banco de dados
- Usuário padrão criado (email: admin@example.com, senha: password)
- Ambiente de monitoramento configurado

## 🧪 Testes

### Backend
```bash
docker-compose exec api php artisan test
```

### Frontend
```bash
docker-compose exec frontend ng test
```

## 📝 Documentação da API

A documentação completa da API está disponível através do Swagger:
- URL: http://localhost:8001/api/documentation

Principais endpoints:
- **Autenticação**: `/api/cadastrar`, `/api/login`, `/api/perfil`, `/api/logout`
- **Contas**: `/api/contas/{id}`, `/api/contas/{id}/depositar`, `/api/contas/{id}/sacar`
- **Transações**: `/api/transacoes`, `/api/transacoes/depositar`, `/api/transacoes/transferir`, `/api/transacoes/{id}/estornar`

## 🔒 Segurança

O sistema implementa diversas camadas de segurança:
- Autenticação via tokens JWT
- Proteção contra CSRF
- Validação rigorosa de inputs
- Sanitização de dados
- Logs de auditoria para transações financeiras

## 🌟 Diferenciais do Projeto

- **Arquitetura Escalável**: Preparada para crescimento e alta demanda
- **Observabilidade Completa**: Monitoramento em tempo real de todos os componentes
- **Containerização**: Ambiente consistente e fácil de implantar
- **Testes Automatizados**: Cobertura abrangente para garantir qualidade
- **Documentação Detalhada**: API completamente documentada via Swagger

---

