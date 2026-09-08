# Google Verification

Use this when submitting Tidimail for Google OAuth verification.

## Basic app details

- App name: `Tidimail`
- User-facing homepage: `https://www.tidimail.com`
- Privacy policy: `https://www.tidimail.com/privacy`
- Terms of service: `https://www.tidimail.com/terms`
- Support email: `tidimail.hello@gmail.com`
- OAuth redirect URI: `https://api.tidimail.com/auth/google/callback`

## OAuth consent screen

- User type: `External`
- App domain homepage: `https://www.tidimail.com`
- Authorized domains: `tidimail.com`
- Developer contact: `tidimail.hello@gmail.com`

## Requested scopes

- `openid`
- `email`
- `profile`
- `https://www.googleapis.com/auth/gmail.modify`

## Scope justification

Tidimail helps a user review senders in Gmail and choose what to do with that sender's existing and future inbox mail. The app needs `gmail.modify` so it can label, archive, restore, and move messages to Gmail Trash after the user explicitly chooses Keep, Digest, Unsubscribe, Undo, or Trash now. Tidimail does not create forwarding rules or Gmail filters.

## What the app reads

Tidimail reads only basic Gmail metadata needed to group and review senders:

- sender name and sender email address
- subject line
- Gmail labels / categories
- message date
- short Gmail snippet
- `List-Unsubscribe` headers when present

Tidimail does not read or store full email bodies or attachments.

## What the app stores

Tidimail stores:

- the signed-in Google account email
- encrypted Google access and refresh tokens so the user can stay connected and daily scans can run
- sender groups
- basic message metadata listed above
- the user's Keep / Digest / Unsubscribe choices
- undo history
- push subscription data if the user allows notifications

Tidimail does not store full message bodies or attachments.

## User control

Users stay in control:

- nothing changes until the user chooses an action
- Keep leaves mail in Gmail
- Digest archives mail and schedules Gmail Trash after 30 days
- Unsubscribe uses one-click unsubscribe when available, archives existing mail, and schedules Gmail Trash after 30 days
- Trash now moves existing mail to Gmail Trash immediately
- Undo is available for 24 hours

## Disconnect and data deletion

Users can remove access inside the app at `https://www.tidimail.com/settings`.

- Disconnect Gmail revokes Google access and deletes stored mail data
- Delete account removes the Tidimail account as well
- Users can also revoke access in Google account permissions: `https://myaccount.google.com/permissions`

## Demo video script

Record this on the live production app, not the fake homepage demo:

1. Open `https://www.tidimail.com`
2. Click Sign in with Google
3. Complete OAuth consent
4. Land back in the app
5. Start a scan
6. Open the sweep / pending senders screen
7. Show one sender each for Keep, Digest, and Unsubscribe
8. Show Trash now on a sender and confirm it
9. Show the action history / undo flow
10. Open Settings
11. Show Disconnect Gmail
12. Mention that users can also delete their account

## Notes for Google's review team

- Tidimail is a Gmail cleanup assistant, not a replacement inbox
- the app works only on the user's own Gmail account
- all mailbox actions happen inside Gmail using `gmail.modify`
- no email is sent as the user except one-click unsubscribe requests triggered by the user when the sender exposes a standard unsubscribe header
