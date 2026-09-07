# Go live

Code can ship the product. Google and a host still have to stay up. A laptop with `php artisan serve` is not launch.

## What is already in the app

- Privacy: `/privacy`
- Terms: `/terms`
- Account: `/settings` — reconnect, disconnect Gmail, delete Tidimail
- Scan errors are plain English. Gmail expiry shows **Gmail expired, tap to reconnect.**
- Daily scan: `tidimail:sync-inbox` at `TIDIMAIL_DAILY_SYNC_AT` (default 06:00)
- Cleanup popup + web push after that scan, if the first sweep is done and new senders need a decision

Paste these URLs into Google Cloud once the site is on https:

- Privacy: `https://YOUR-DOMAIN/privacy`
- Terms: `https://YOUR-DOMAIN/terms`
- Homepage: `https://YOUR-DOMAIN`
- Authorized redirect: `https://YOUR-API-HOST/auth/google/callback`

## 1. HTTPS + your domain

Install, home-screen app, and push notifications need `https://`. `localhost` is only for you.

Suggested split:

| Piece | Host |
|---|---|
| Next.js (`frontend/`) | Vercel (or similar) on `https://app.yourdomain.com` or the apex |
| Laravel (`backend/`) | Render / Railway / a VPS on `https://api.yourdomain.com` |

Set:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com
FRONTEND_URL=https://app.yourdomain.com
CORS_ALLOWED_ORIGINS=https://app.yourdomain.com
GOOGLE_REDIRECT_URI=https://api.yourdomain.com/auth/google/callback
NEXT_PUBLIC_API_URL=https://api.yourdomain.com
```

Create a **new** Google OAuth Web client (or add origins) for those https URLs. The localhost client is not enough.

Trust proxies is already `*` so HTTPS behind Render/Railway/Cloudflare works.

## 2. A real server for daily scan

Scan and notifications only run if **all three** stay up:

1. Laravel web (`php artisan serve` locally, or php-fpm / the host’s web process)
2. Queue worker: `php artisan queue:work --timeout=900 --tries=1`
3. Scheduler: `php artisan schedule:work`  
   or cron: `* * * * * cd /var/www/backend && php artisan schedule:run`

`QUEUE_CONNECTION` must not be `sync` in production. Use `database` (jobs table already migrated) or Redis.

`backend/Procfile` maps those three processes for Render/Railway.

Without the worker, “Scan” sits in `queued`. Without the scheduler, there is no 6am scan and no next-morning popup.

## 3. Google OAuth: Testing → Production

Until Google approves `gmail.modify`, **only test users** can sign in.

1. Google Cloud → APIs & Services → OAuth consent screen  
2. App information: name **Tidimail**, privacy URL, terms URL  
3. Scopes: `openid`, `email`, `profile`, `https://www.googleapis.com/auth/gmail.modify`  
4. Add yourself as a test user while the app is in Testing  
5. Publish the consent screen (Production)  
6. Submit **gmail.modify** for restricted-scope verification  

Google will ask what you read (headers, subject, labels, snippet, unsubscribe), what you never store (full bodies, attachments), and how to disconnect (`/settings` + [Google permissions](https://myaccount.google.com/permissions)). Use `/privacy` as the answer.

Verification can take days or weeks. Plan a one-person beta with test users first.

## 4. One-person launch path

Do this on a messy real inbox, on https, with worker + scheduler running.

**Day 0 — under 3 minutes**

1. Open the live site → Sign in with Google → allow Gmail  
2. Scan. Review Mode should unlock once the noisiest senders are grouped (you do not wait for thousands of messages).  
3. Keep / Digest / Unsubscribe, or apply recommendations  
4. Confirm Undo still lists the last action  
5. Allow notifications when asked  
6. Install the home-screen app  

**Next morning**

1. Confirm the 06:00 job ran (`last_synced_at` moved; Laravel log / host logs)  
2. Open the app — cleanup popup if new senders arrived  
3. If you turned on notifications, the push should open Sweep  

If the morning is quiet, `php artisan tidimail:sync-inbox --force` on the **server** (not the laptop) then refresh Sweep.

## Local (not launch)

```bash
# terminal 1
cd backend && php artisan serve --host=127.0.0.1 --port=8000

# terminal 2
cd backend && php artisan queue:work --timeout=900 --tries=1

# terminal 3
cd backend && php artisan schedule:work

# terminal 4
cd frontend && npm run dev
```

Or `bash scripts/dev-backend.sh` for the three Laravel processes. Postgres: `docker compose up -d` (host port **5434**).
