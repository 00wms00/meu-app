# Meu App — Gestão de Compras Domésticas

Aplicativo web para controle inteligente de compras domésticas. Permite importar notas fiscais, categorizar produtos, monitorar preços, criar listas de compras e analisar encartes com inteligência artificial.

## Funcionalidades

- 📄 **Importação de Notas Fiscais** — parse e armazenamento de NF-e
- 📦 **Catálogo de Produtos** — agrupamento automático e manual de produtos similares
- 🏷️ **Categorias** — classificação por categoria com atualização em lote
- 📊 **Dashboard** — visão geral dos gastos e compras
- 📈 **Relatórios** — relatório mensal e por período
- 💰 **Orçamento** — definição e controle de orçamento por categoria
- 🔔 **Alertas de Preço** — notificação quando produto atingir preço-alvo
- 🛒 **Listas de Compras** — criação inteligente com sugestões baseadas no histórico
- 🤖 **ML de Agrupamento** — machine learning para identificar produtos similares
- 🖼️ **Encartes com IA** — upload e análise de encartes de supermercado via IA
- 📸 **Fotos de Produtos** — upload de imagens por produto

## Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.3 + Laravel 13 |
| Banco de dados | PostgreSQL 18 |
| Frontend | Blade + Tailwind CSS + Vite |
| Infraestrutura | Docker (Laravel Sail) |
| Testes | PHPUnit 12 |
| Autenticao | Laravel Breeze |

## Pré-requisitos

- Docker e Docker Compose instalados
- PHP 8.3+ e Composer (para rodar sem Docker)
- Node.js 20+ e npm

## Instalação rápida (recomendado)

```bash
# 1. Clone o repositório
git clone https://github.com/00wms00/meu-app.git
cd meu-app

# 2. Instale tudo com um comando só
composer setup
```

O comando `composer setup` executa automaticamente:
- `composer install`
- Cria o `.env` a partir do `.env.example`
- Gera a `APP_KEY`
- Executa as migrations
- `npm install` + `npm run build`

## Executando com Docker (Laravel Sail)

```bash
# Suba os containers (app + PostgreSQL)
./vendor/bin/sail up -d

# Rode as migrations
./vendor/bin/sail artisan migrate

# Inicie o servidor de desenvolvimento
./vendor/bin/sail npm run dev
```

Acesse em: [http://localhost](http://localhost)

## Executando sem Docker

```bash
# Configure o .env com suas credenciais do banco
cp .env.example .env
php artisan key:generate

# Rode as migrations
php artisan migrate

# Inicie tudo em paralelo (servidor, queue, logs e Vite)
composer dev
```

## Configuração do `.env`

Copie `.env.example` e ajuste as variáveis principais:

```env
APP_NAME="Meu App"
APP_URL=http://localhost

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=meu_app
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

## Estrutura do Projeto

```
app/
├── Http/Controllers/   # Controllers da aplicação
├── Models/             # Eloquent Models
├── Services/           # Lógica de negócio
├── Observers/          # Observadores de Model
└── Policies/           # Autorizações
database/
├── migrations/         # Migrations do banco
└── seeders/            # Seeders
resources/
├── views/              # Templates Blade
└── js/ + css/          # Assets do frontend
routes/
├── web.php             # Rotas web (todas protegidas por auth)
└── auth.php            # Rotas de autenticao
```

## Testes

```bash
composer test
# ou
php artisan test
```

## Licença

Este projeto é de uso pessoal.
