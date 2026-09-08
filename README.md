# tidimail
Tidimail is a web-based email management platform designed to help users automatically clean, organize, and prioritize their inboxes across one or more email accounts. The platform combines email unsubscribe features with intelligent background automation to reduce inbox clutter and improve productivity.

The MVP is a **2-minute sender review ritual**, not a Gmail clone. See [docs/mvp-screens.md](docs/mvp-screens.md) for first-run, daily sweep, and sender passport screens wired to the Laravel API below.

### Sender-review API (Laravel)

After Google OAuth (`GET /auth/google/redirect` → `/auth/google/callback`), the SPA stores the bearer token and calls:

| Method | Path | Purpose |
|---|---|---|
| GET | `/api/me` | User, accounts, scan status |
| POST | `/api/accounts/{id}/sync` | Queue a 30-day Gmail metadata sync |
| GET | `/api/sweep` | First-run or daily sender stack + Quiet Score |
| POST | `/api/sweep/apply-recommendations` | Batch digest/unsubscribe pending recs |
| POST | `/api/sweep/complete` | Mark first-run done |
| GET | `/api/senders` | Filterable sender index |
| GET | `/api/senders/{id}` | Sender passport |
| POST | `/api/senders/{id}/review` | `{ "action": "keep" \| "digest" \| "unsubscribe" }` |
| GET | `/api/actions` | 24-hour undo tray |
| POST | `/api/actions/{id}/undo` | Restore inbox + sender status |
| POST | `/api/auth/logout` | Revoke current token |
| POST | `/api/auth/disconnect` | Revoke Google, delete stored mail data |
| DELETE | `/api/me` | Disconnect and delete the Tidimail user |

Public pages: `/privacy`, `/terms`. Account: `/settings`. Launch checklist: [docs/go-live.md](docs/go-live.md).

Review mode is the default: Gmail is only labeled/archived after an explicit Keep / Digest / Unsubscribe (or “apply recommendations”). Every applied action is undoable for 24 hours. Digest and Unsubscribe mail is moved to Gmail Trash after 30 days; Keep is never purged. Already-reviewed senders auto-apply on later syncs.

```bash
cd backend
php artisan test
```

### Frontend (Next.js)

The SPA implements first-run, daily sweep, sender passport, and the undo tray. You can click through the demo without Google (fake mail).

```bash
cd frontend
cp .env.example .env.local
npm install
npm run dev
```

Open http://localhost:3001. On a phone, add Tidimail to the home screen (Share → Add to Home Screen on iOS, or Install on Android) to use it like an app. Mail still lives in Gmail.

Choose **Try the demo**, or **Sign in with Google** (Laravel must be running on `NEXT_PUBLIC_API_URL`, default `http://localhost:8000`).



Recommended Stack (Phase 1 - Best Choice)

- Frontend: React + Next.js (TypeScript) with Tailwind CSS for styling. Build a small SPA that talks to the Laravel API.
- Backend API: Laravel (PHP) — use Laravel Socialite for Google OAuth and Laravel Sanctum (or Passport if cross-domain) for auth.
- Background processing: Laravel Queues with Redis and Laravel Horizon for monitoring.
- Database: PostgreSQL (use managed provider such as Render, Railway, Supabase, or Neon).
- AI / NLP: Server-side calls to OpenAI (or a comparable LLM) for classification and urgency scoring; use `chrono-node` or LLM-assisted parsing for due-date extraction. Cache model responses.
- Storage: S3-compatible storage for attachments (AWS S3, DigitalOcean Spaces, or MinIO).
- Hosting: Render or Railway for backend + workers; Vercel or static hosting for Next.js frontend.

Quick setup steps (developer flow)

1. Clone the repo and create two apps: `frontend/` (Next.js) and `backend/` (Laravel).

2. Backend initial setup

	- Install dependencies and create `.env` (set `DB_*`, `REDIS_*`, `APP_URL`, `GOOGLE_CLIENT_ID`, `GOOGLE_CLIENT_SECRET`, `OPENAI_KEY`).

	```bash
	cd backend
	composer install
	cp .env.example .env
	php artisan key:generate
	php artisan migrate
	```

	- Configure PostgreSQL and Redis connection strings in `.env`.

3. Google OAuth (server-side)

	- Use Laravel Socialite to implement Google OAuth. Request scopes conservatively, for example: `https://www.googleapis.com/auth/gmail.modify` (read/modify/label).
	- Store access and refresh tokens encrypted in the database.

4. Queues and workers

	- Configure `QUEUE_CONNECTION=redis` and run a worker and Horizon during development:

	```bash
	php artisan queue:work --tries=3
	php artisan horizon
	```

	- Important: Horizon requires Linux/WSL2 with the `pcntl` and `posix` PHP extensions. On Windows, use `php artisan queue:work` for local development, or run Horizon inside WSL2/Docker on a Linux-compatible environment.

	- Create periodic jobs for inbox sync and rate-limited unsubscribe flows.

5. Frontend initial setup

	```bash
	cd frontend
	npm install
	cp .env.example .env.local
	npm run dev
	```

	- Frontend will authenticate via your Laravel API (use cookie auth for same-domain, or OAuth tokens if cross-domain).

6. AI and parsing

	- Implement classification as a two-stage pipeline: fast rule-based heuristics first, fallback to OpenAI classification for uncertain cases. Batch and cache model calls to control cost.
	- Use `chrono-node` (or LLM) to extract dates from message text.

7. Unsubscribe flow

	- Detect `List-Unsubscribe` header; fall back to HTML unsubscribe link scraping. Perform unsubscribe in a background job with retries and logging.

8. Run locally

	- Start Postgres and Redis (Docker recommended), run migrations, start backend + worker, then run frontend.

	```bash
	# from repo root
	docker compose up -d postgres redis
	cd backend && php artisan migrate && php artisan queue:work
	cd frontend && npm run dev
	```

Notes & best practices

- Use least-privilege Gmail scopes and request modify only when labeling/archiving/unsubscribing is necessary.
- Encrypt tokens at rest and rotate credentials in your provider dashboards.
- Monitor Gmail API usage and implement exponential backoff and idempotency for jobs.
- Start in "review mode" (suggested actions require user confirmation) before enabling fully automatic actions.

If you want, I can scaffold the `frontend/` and `backend/` starter directories (Next.js + Laravel) and add a minimal Google OAuth demo. Tell me which scaffold you prefer first.

Commands
========

This section collects the full commands referenced above so you can copy and run them. Explanations are short and placed where helpful.

Prerequisites (verify installed)

```bash
# Git
git --version

# Docker
docker --version

# Node and npm
node --version
npm --version

# PHP and Composer
php -v
composer --version
```

Start core services (Postgres + Redis) using Docker

Run Postgres and Redis containers for local development. Adjust ports or use `docker compose` if you prefer.

```bash
# Postgres
docker run -d --name tidimail-postgres -e POSTGRES_PASSWORD=postgres -e POSTGRES_USER=postgres -e POSTGRES_DB=tidimail -p 5432:5432 postgres:15

# Redis
docker run -d --name tidimail-redis -p 6379:6379 redis:7

# (Optional) Bring up compose if you have docker-compose.yml
docker compose up -d
```

Backend: scaffold and initialize Laravel app

Create a backend app, install packages, and generate the app key.

```bash
# create backend folder and Laravel app (run from repo root)
mkdir -p backend
cd backend
composer create-project laravel/laravel . "10.*"

# required packages
composer require laravel/socialite laravel/sanctum laravel/horizon guzzlehttp/guzzle openai-php/client predis/predis


# copy env and generate key
cp .env.example .env
# edit .env to set DB_/REDIS_/GOOGLE_/OPENAI_ values before migrating
php artisan key:generate
php artisan migrate

# publish vendor assets (Sanctum, Horizon)
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"
```

Helpful model/controller/job scaffolding

```bash
# create basic models, controller, jobs, and a command
php artisan make:model Account -m
php artisan make:model Message -m
php artisan make:controller Auth/GmailController
php artisan make:job SyncInboxJob
php artisan make:job UnsubscribeJob
php artisan make:command SyncInboxCommand
```

Set `.env` essentials (example values)

```
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

DB_CONNECTION=pgsql
DB_HOST=host.docker.internal # or 127.0.0.1
DB_PORT=5432
DB_DATABASE=tidimail
DB_USERNAME=postgres
DB_PASSWORD=postgres

GOOGLE_CLIENT_ID=your_google_client_id
GOOGLE_CLIENT_SECRET=your_google_client_secret
GOOGLE_REDIRECT_URI=https://your-backend.example.com/auth/google/callback
OPENAI_API_KEY=sk-...
```

Run migrations

```bash
php artisan migrate
```

If migration fails with `password authentication failed for user "postgres"`, make sure your Postgres container was created with `POSTGRES_PASSWORD=postgres` or update `DB_PASSWORD` in `.env` to match the actual container password. If you are unsure, delete and recreate the container with the credentials above.

Start worker and Horizon (development)

Run these in separate terminals. Horizon provides a UI at `/horizon` on Linux/WSL2, but on Windows use only the queue worker.

```bash
# start a worker
php artisan queue:work --tries=3 --sleep=3

# start Horizon (monitoring, Linux/WSL2 only)
php artisan horizon
```

Schedule and run repeated jobs (scheduler)

Register your `SyncInboxCommand` or job in `app/Console/Kernel.php` schedule method, then run:

```bash
# run the scheduler in development (keeps running)
php artisan schedule:work
```

AI / NLP: install OpenAI client

```bash
composer require openai-php/client
# set OPENAI_API_KEY in .env
```

Optional: Date-extraction microservice (chrono-node)

Create a tiny Node service that Laravel can call for robust date parsing.

```bash
# from repo root
mkdir -p chrono-service
cd chrono-service
npm init -y
npm install express chrono-node cors
```

Create `index.js` with a small server that exposes `/parse` and run it:

```bash
node index.js
# or use nodemon for auto-reload during development
npx nodemon index.js
```

Frontend: scaffold Next.js + Tailwind

```bash
# from repo root
npx create-next-app@latest frontend --typescript --app
cd frontend

# install Tailwind
npm install -D tailwindcss postcss autoprefixer
npx tailwindcss init -p

# other dependencies
npm install axios @tanstack/react-query zustand lucide-react

# run dev server
npm run dev
```

Connect frontend and backend

- If frontend and backend share the same domain, use Sanctum cookie auth. If cross-domain, use Passport or token-based auth.
- Adjust CORS and `SANCTUM_STATEFUL_DOMAINS` accordingly in `backend/.env`.

Unsubscribe flow (implementation notes)

- Detect `List-Unsubscribe` header when ingesting messages and schedule `UnsubscribeJob`.
- Use Guzzle in the job to follow unsubscribe links or send unsubscribe requests.

Run full stack locally (quick reference)

```bash
# start Postgres + Redis (docker)
docker compose up -d

# backend: migrate and serve
cd backend
php artisan migrate
php artisan serve --host=0.0.0.0 --port=8000

# in another terminal: start worker
cd backend
php artisan queue:work

# optional: start Horizon
php artisan horizon

# start chrono service
cd chrono-service
node index.js

# frontend
cd frontend
npm run dev
```

Testing and linting

```bash
# backend tests (PHPUnit)
cd backend
./vendor/bin/phpunit

# frontend lint and tests
cd frontend
npm run lint
npm run test
```

Deployment notes (commands depend on provider)

- Build frontend for production:

```bash
cd frontend
npm run build
```

- For Laravel, use a Dockerfile or deploy to Render/Railway and run migrations and queue workers as separate services.

Security & operational reminders

- Protect Google OAuth credentials and encrypt stored tokens.
- Monitor Gmail API usage and implement backoff.
- Start users in review mode before enabling auto actions.

