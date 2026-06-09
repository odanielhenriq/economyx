# Economyx

Aplicação de finanças pessoais e familiares construída com Laravel 12.

## Funcionalidades

- Dashboard mensal (receitas, despesas, faturas, empréstimos)
- Transações, cartões de crédito e contas fixas
- Extrato de cartão com parcelas automáticas
- Convite de parceiro por link
- Importação de extrato via IA (Anthropic Claude — opcional)
- Exportação CSV e JSON para análise

## Requisitos

- PHP 8.2+
- Composer
- Node.js 18+
- SQLite (padrão) ou MySQL

## Instalação (Herd / local)

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

Os dados de referência (categorias, tipos, formas de pagamento) são criados automaticamente pela migration `seed_reference_data`.

## Desenvolvimento

```bash
composer dev
```

Ou separadamente:

```bash
php artisan serve
npm run dev
```

## Variáveis de ambiente

| Variável | Descrição |
|----------|-----------|
| `APP_NAME` | Nome exibido (recomendado: Economyx) |
| `ANTHROPIC_API_KEY` | Chave para importação de extrato via IA (opcional) |

## Fluxo de uso

1. Registrar em `/register` — você cai direto no dashboard
2. Lançar transações ou importar extrato
3. Opcional: convidar parceiro em **Cadastros → Parceiros**
4. Acompanhar no **Dashboard** e em **Cartões**

## Testes

```bash
php artisan test
```

## Seeders locais (dados de desenvolvimento)

Para dados de exemplo com usuários reais de dev:

```bash
php artisan db:seed --class=Database\\Seeders\\LocalDevSeeder
```

## Licença

MIT
