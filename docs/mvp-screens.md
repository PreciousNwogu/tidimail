# Tidimail MVP screens

The product is a 2-minute review ritual, not a Gmail clone. Every screen below is wired to a Laravel endpoint so the frontend can stay a thin SPA.

Auth: Google OAuth, then `Authorization: Bearer {token}` on `/api/*`.

---

## Shared chrome

Always-on after login:

- Quiet Score (0–100) and a one-line status (“42 senders still need a decision”).
- **Sweep** (home), **Undo** (24h undo tray), account email.
- Never show a message list as the home view.

States every screen must handle: loading, empty, error, Gmail reconnect required.

---

## 1. First-run

Goal: connect Gmail, scan ~30 days, and get a visible cleanup in under 5 minutes.

### 1.1 Landing (`/`)

- Kicker: “Before we change anything”
- Headline: “Review first. Then you decide.”
- Body: Tidimail starts in Review Mode. We only look at basic email details (sender, subject, labels, unsubscribe information). We don’t create a new inbox or change emails during review. Nothing is changed until you choose Keep, Digest, or Unsubscribe. Undo any decision within 24 hours. After 30 days, Digest and Unsubscribe mail is moved to Gmail Trash. You’re always in control.
- Primary: **Continue with Google**.
- Fine print: we request `gmail.modify` so we can label and archive. We never send email as you except one-click unsubscribe.

**API:** `GET /auth/google/redirect`

### 1.2 OAuth return (`/auth/callback?token=`)

- Store the token, then immediately `GET /api/me`.
- If the account has `last_synced_at = null`, go to Scanning. Else go to Daily Sweep.

**API:** `GET /api/me`

### 1.3 Permission & expectation (`/onboarding/trust`)

Short, one screen, no carousel. Same story as landing.

- Tidimail starts in **Review Mode**. We only look at sender, subject, labels, and unsubscribe information. We don’t create a new inbox or change emails during review.
- Nothing is changed until you choose **Keep**, **Digest**, or **Unsubscribe**.
- You can undo any decision within 24 hours.
- After 30 days, Digest and Unsubscribe mail is moved to Gmail Trash.
- You’re always in control.

Primary: **Scan the last 30 days**.

**API:** `POST /api/accounts/{account}/sync`

### 1.4 Scanning (`/onboarding/scan`)

- Determinate if `sync.status` is `running` with counts; otherwise pulse.
- Copy: “Grouping by sender, not by message.”
- Poll `GET /api/me` or `GET /api/accounts` until `last_synced_at` is set.

Do not dump messages on this screen.

### 1.5 Sweep summary (`/onboarding/summary`)

Hero numbers from `GET /api/sweep`:

| Stat | Source |
|---|---|
| Senders found | `stats.senders_total` |
| Emails scanned | `stats.messages_scanned` |
| Suggested unsubscribe | `stats.recommended_unsubscribe` |
| Suggested digest | `stats.recommended_digest` |
| Suggested keep | `stats.recommended_keep` |

Primary: **Start the sweep** → sender stack.
Secondary: **Apply all unsubscribe + digest recommendations** → confirm modal → `POST /api/sweep/apply-recommendations`.

The batch path is the “wow” moment. The stack is for people who want to look first.

### 1.6 Sender stack (`/onboarding/sweep`)

One sender card at a time (or a vertical stack of ~5), **not** a table.

Card content (from `GET /api/sweep?mode=first_run`):

- Avatar/initials, display name, email, domain
- “12 emails · 11 unread · last mail 2 days ago”
- Category chip (promo / newsletter / person / receipt)
- Recommendation + reason (the sentence from `recommendation_reason`)
- 2 sample subjects

Actions:

| Button | Call |
|---|---|
| Keep | `POST /api/senders/{id}/review` `{ "action": "keep" }` |
| Digest | `{ "action": "digest" }` |
| Unsubscribe | `{ "action": "unsubscribe" }` |
| Open passport | `GET /api/senders/{id}` |

Unsubscribe is disabled with a tooltip if `has_list_unsubscribe` is false (“No unsubscribe header — digest instead”).

Progress: “7 of 42 senders”. Skip is allowed; skipped stay `pending`.

### 1.7 Applied (`/onboarding/done`)

- “Archived 847 emails. Unsubscribed 12 senders. 3 still need you.”
- Link: **Undo tray**.
- CTA: **That’s my daily sweep** → `POST /api/sweep/complete` → Daily Sweep next time.

---

## 2. Daily sweep

Goal: 8 decisions, then you’re done. New mail from already-reviewed senders is applied in the background during sync.

### 2.1 Home (`/sweep`)

**API:** `GET /api/sweep`

Header:

- Quiet Score + delta vs last sweep
- “8 senders to review · 2 need you today”

Sections, in order:

1. **Needs you today** — `stats.needs_you` / `senders` where `recommendation = keep` and recent unread. These are people and receipts, not promo.
2. **Today’s 8** — pending senders, highest-impact first (unsubscribe rec + volume).
3. **Already handled** (collapsed) — counts only: keep / digest / unsubscribed.

Empty state (the win): “Inbox is quiet. Next scan runs in the background.”

Primary on each card: same Keep / Digest / Unsubscribe as first-run.

### 2.2 Apply + toast

After each review, optimistic remove from the stack.

Toast: “Moved 14 emails from Store X to Digest.” Action: **Undo** → `POST /api/actions/{id}/undo`.

If unsubscribe is queued: “Unsubscribe sent. We’ll watch if they mail you again.”

### 2.3 Undo tray (`/actions`)

**API:** `GET /api/actions`

List of last-24h actions: type, sender, message count, time remaining to undo, proof status for unsubscribes (`pending` / `confirmed` / `failed`).

Failed unsubscribe: keep the archive, show **Try again** or **Keep digesting**.

Expired rows are visible as history but not undoable.

### 2.4 Reconnect

If Gmail returns 401 and refresh fails: banner “Reconnect Google” → `GET /auth/google/redirect`.

---

## 3. Sender passport

Goal: enough evidence to decide in 15 seconds, and a place to change your mind later.

### 3.1 Passport (`/senders/{id}`)

**API:** `GET /api/senders/{id}`

**Header**

- Name, email, domain
- Current status chip (pending / keep / digest / unsubscribed)
- Last reviewed at

**Recommendation**

- Keep / Digest / Unsubscribe
- `recommendation_reason` in plain language
- “Based on Gmail category, volume, unread ratio, and List-Unsubscribe — not a black box.”

**Stats**

- Message count, unread count, first seen, last mail
- Has unsubscribe header (yes/no)
- Gmail categories seen (promotions, social, updates)

**Sample mail** (5 most recent)

- Subject, snippet, relative date, read/unread
- No full body in MVP (metadata only)

**Actions** (same three), plus:

- If already digest → Keep (restore to inbox) or Unsubscribe
- If already unsubscribed → Keep (restore) ; do not re-fire HTTP unsubscribe

**History**

- Previous inbox actions for this sender (applied / undone / unsubscribe proof)

### 3.2 Sender index (`/senders`) — optional in MVP

Filter chips: Pending / Keep / Digest / Unsubscribed.
Search by name or domain.

**API:** `GET /api/senders?status=pending&q=`

Useful after first-run, not on the happy path.

---

## Screen → API map

| Screen | Methods |
|---|---|
| Landing | `GET /auth/google/redirect` |
| OAuth return | `GET /auth/google/callback` → `{frontend}/auth/callback?token=` |
| Me / scan status | `GET /api/me` |
| Kick off scan | `POST /api/accounts/{account}/sync` |
| First-run + daily home | `GET /api/sweep` |
| Apply all recs | `POST /api/sweep/apply-recommendations` |
| Finish first-run | `POST /api/sweep/complete` |
| Review one sender | `POST /api/senders/{sender}/review` |
| Passport | `GET /api/senders/{sender}` |
| Sender list | `GET /api/senders` |
| Undo tray | `GET /api/actions` |
| Undo | `POST /api/actions/{action}/undo` |
| Logout | `POST /api/auth/logout` |

Review body: `{ "action": "keep" \| "digest" \| "unsubscribe" }`.

Apply-recommendations body: `{ "actions": ["unsubscribe", "digest"] }` (only pending senders whose `recommendation` is in that list).

---

## Copy rules

- Talk about **senders**, never “we processed 1,847 messages” as the main story.
- Always say **why**.
- Always offer **undo**.
- Never auto-unsubscribe on day one; first-run is review or an explicit “apply recommendations” confirm.

## Out of MVP (do not design screens for these yet)

Compose, thread view, chat-with-inbox, attention budget, receipt vault, household sharing, Quiet Score history charts.
