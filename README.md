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

## Deploy (Render + Neon)

### 1. Banco no Neon

1. Crie um projeto em [console.neon.tech](https://console.neon.tech)
2. Copie a connection string PostgreSQL

### 2. App no Render

1. **New Web Service** → repositório `economyx`
2. **Runtime:** Docker
3. **Instance type:** Free
4. Variáveis de ambiente:

| Variável | Valor |
|----------|-------|
| `APP_NAME` | `Economyx` |
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` |
| `APP_KEY` | Gerar com `php artisan key:generate --show` |
| `APP_URL` | URL do Render (ex.: `https://economyx.onrender.com`) |
| `DB_CONNECTION` | `pgsql` |
| `DB_URL` | Connection string do Neon |
| `SESSION_DRIVER` | `database` |
| `SESSION_SECURE_COOKIE` | `true` |
| `CACHE_STORE` | `database` |
| `QUEUE_CONNECTION` | `database` |
| `ANTHROPIC_API_KEY` | Opcional |

5. **Health Check Path:** `/up`
6. Deploy — migrations rodam automaticamente no container

### Build local (opcional)

```bash
docker build -t economyx .
docker run --rm -p 8080:8080 --env-file .env economyx
```

## Testes

```bash
php artisan test
```

## Seeders locais (dados de desenvolvimento)

Com `APP_ENV=local`, `php artisan migrate:fresh --seed` já cria usuários de dev, cartões e vínculo entre parceiros.

| E-mail | Senha |
|--------|-------|
| `daniel.henrique00@hotmail.com` | `password` |
| `joycebvb@gmail.com` | `password` |

Para recarregar só os dados de dev (sem recriar tabelas):

```bash
php artisan db:seed --class=Database\\Seeders\\LocalDevSeeder
```

Transações reais de dev exigem o arquivo local `RealTransactionsSeeder.php` (não versionado).

## Licença

MIT
