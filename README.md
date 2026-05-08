# 🏠 Meu App — Gestão Doméstica Integrada

Aplicativo web para gestão doméstica completa: compras de supermercado, finanças pessoais e controle de veículos.

---

## 🎯 Funcionalidades

### 📄 Notas Fiscais
- **Importação de NFC-e** — parse e armazenamento automático
- **Lançamento Manual** — registro de compras sem nota fiscal
- **Histórico de Notas** — visualização e edição de notas importadas

### 📦 Produtos
- **Catálogo de Produtos** — todos os produtos comprados
- **Normalização** — nomes padronizados com sugestão automática
- **Categorização** — classificação por categoria (individual ou em lote)
- **Agrupamento Inteligente** — produtos iguais com nomes diferentes
- **Histórico de Preços** — evolução do preço com gráficos
- **Fotos** — upload de imagem por produto
- **Similares** — algoritmo de similaridade entre produtos

### 🛒 Compras
- **Listas de Compras** — criação manual ou rápida por categoria
- **Planejamento Inteligente** — sugestões baseadas no histórico
- **Tendências** — análise de gastos e frequência
- **Sazonalidade** — histórico mensal de compras

### 🏷️ Ofertas e Alertas
- **Ofertas** — cadastro manual de promoções
- **Encartes com IA** — upload e análise via Google Gemini
- **Comparação de Preços** — oferta vs histórico
- **Alertas de Preço** — notificação quando produto atinge limite

### 💰 Finanças
- **Receitas** — salários, freelances, outras entradas
- **Despesas Fixas** — contas recorrentes mensais
- **Despesas Variáveis** — gastos do dia a dia
- **Cartões de Crédito** — cadastro com limite, fechamento e vencimento
- **Compras Parceladas** — geração automática de parcelas
- **Faturas** — visão detalhada por cartão e mês
- **Orçamento** — definição e controle por categoria
- **Fluxo de Caixa** — visão consolidada de receitas e despesas

### 🚗 Veículos
- **Cadastro de Veículos** — apelido, marca, modelo, ano, placa
- **Abastecimentos** — valor, litros, km, posto, tipo de combustível
- **Cálculo Automático** — consumo médio (km/L) e custo por km
- **Despesas** — manutenção, seguro, impostos, pedágio
- **Lembretes** — alertas de manutenção por km e/ou data
- **Relatório Mensal** — combustível, manutenção, outros gastos
- **Comparativo de Postos** — ranking de preço por litro
- **Gráficos** — evolução de consumo, custo e preços

### 📊 Dashboard e Relatórios
- **Dashboard** — visão geral de gastos, notas e alertas
- **Relatório Mensal** — produtos, categorias, gastos
- **Relatório por Período** — datas flexíveis
- **Gráficos** — evolução de gastos e consumo

---

## 🏗️ Stack

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.3+ + Laravel 13 |
| Banco de dados | PostgreSQL 18 |
| Frontend | Blade + Tailwind CSS + Alpine.js + Vite |
| Gráficos | Chart.js |
| Infraestrutura | Docker (Laravel Sail) |
| Testes | PHPUnit 12 |
| Autenticação | Laravel Breeze |
| IA | Google Gemini (análise de encartes) |

---

## 📋 Pré-requisitos

- Docker e Docker Compose
- PHP 8.3+ e Composer (para rodar sem Docker)
- Node.js 20+ e npm

---

## 🚀 Instalação rápida (recomendado)

```bash
# 1. Clone o repositório
git clone https://github.com/00wms00/meu-app.git
cd meu-app

# 2. Instale tudo com um comando só
composer setup
