# Insted · Integração JACAD

Sistema Laravel para integrar dados acadêmicos da plataforma **SWA.JACAD** (instância Insted)
para um banco **PostgreSQL**, com painel administrativo modular. O primeiro módulo entregue
importa **matrículas de todos os anos/períodos letivos**.

## Stack

- **PHP 8.5** + **Laravel 13**
- **PostgreSQL** (`localhost:5432`, database `insted`)
- Painel em Blade com CSS próprio (paleta da marca Insted — teal `#17BEB8` + grafite `#2C2F36`),
  sem etapa de build (roda direto com `php artisan serve`).

## Estrutura (modular)

| Módulo | Onde |
|---|---|
| **Parâmetros de API** (base_url, usuário, token por integração) | `app/Models/ApiParametro.php`, `parametros/*` |
| **Cliente JACAD** (autenticação + access token cacheado) | `app/Services/Jacad/JacadClient.php` |
| **Ingestão de matrículas** (períodos → matrículas por cursor) | `app/Services/Jacad/MatriculaIngestService.php` |
| **Command de sincronização** | `app/Console/Commands/SyncMatriculas.php` |
| **Painel** (Dashboard, Matrículas, Períodos, Sincronização, Parâmetros) | `app/Http/Controllers/*`, `resources/views/*` |

Tabelas: `api_parametros`, `periodos_letivos`, `matriculas`, `ingestao_logs`.

## Como a API JACAD funciona (descoberto na integração)

- **Autenticação:** `POST /api/v1/auth/token` com header `token: <chave de API>` →
  retorna `token` (access token JWT) + `expiresIn`.
- **Chamadas autenticadas:** header `Authorization: <access token>`.
- **Organizações:** `GET /api/v1/basicos/organizacoes?pageSize=` (campo id = `idOrganizacao`;
  INSTED = `0`, Escola de Saúde = `1`).
- **Períodos letivos:** `GET /api/v1/academico/periodos-letivos/?idOrg=&pageSize=&currentPage=`
  (`idOrg` e `pageSize` obrigatórios; `currentPage` é 0-indexado).
- **Matrículas:** `GET /api/v1/academico/matriculas?idPeriodoLetivo=&pageSize=&currentPage=`
  (0-indexado, `page.totalPages`).
- **Rate limit:** 10 req/s por IP (bloqueio técnico) + filtro de negócio por hora.

> **Nota:** o endpoint **v2** de matrículas (`/api/v2/academico/matriculas`) retorna um erro de
> SQL no backend do JACAD para o usuário de integração; por isso a ingestão usa o **v1**.

## ⚠️ Pré-requisito: liberação de IP

O token JACAD é **restrito por IP**. Enquanto o IP do servidor não estiver autorizado, a API
responde `HTTP 422 — "A autenticação deste token não está autorizada para o IP 'x.x.x.x'"`.

No painel JACAD: **Integrações → API de Integrações → Tokens de Acesso** → autorizar o IP
onde este sistema roda. (IP detectado nos testes: `189.124.14.255`.)

## Setup

```bash
# 1. Dependências (nesta máquina php + composer foram instalados via Scoop)
composer install

# 2. Ambiente
cp .env.example .env
php artisan key:generate
# edite .env: DB_PASSWORD e JACAD_TOKEN

# 3. Banco (o database 'insted' precisa existir)
php artisan migrate --seed

# 4. Rodar
php artisan serve
# painel em http://127.0.0.1:8000
```

## Sincronização

```bash
# Todos os anos
php artisan jacad:sync-matriculas

# Apenas um ano
php artisan jacad:sync-matriculas --ano=2025

# Apenas atualizar a lista de períodos letivos
php artisan jacad:sync-matriculas --somente-periodos
```

Também é possível disparar pelo painel em **Sincronização**. Para grandes volumes, prefira o
terminal (ou configure uma fila com `php artisan queue:work`).

## Observações de ambiente (Windows / Scoop)

- PHP e Composer instalados via Scoop (`~/scoop`), sem admin.
- `php.ini`: habilitadas as extensões `pdo_pgsql, openssl, mbstring, curl, zip, intl, gd, ...`
  e configurado `curl.cainfo`/`openssl.cafile` apontando para `extras/ssl/cacert.pem`
  (necessário para o SSL do PHP no Windows).
