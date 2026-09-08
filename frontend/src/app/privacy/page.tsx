import Link from "next/link";
import { BrandLogo } from "@/components/BrandLogo";
import { SiteFooter } from "@/components/SiteFooter";

export const metadata = {
  title: "Privacy — Tidimail",
  description: "What Tidimail reads, stores, and never keeps.",
};

export default function PrivacyPage() {
  return (
    <div className="relative z-10 mx-auto min-h-dvh max-w-2xl px-5 py-[max(2rem,env(safe-area-inset-top))]">
      <BrandLogo className="h-16 object-left" />
      <p className="kicker mt-10">Privacy</p>
      <h1 className="mt-3 font-serif text-4xl tracking-tight">What Tidimail sees — and what it never keeps.</h1>
      <p className="mt-4 text-sm text-ink-700">Last updated 7 September 2026.</p>

      <div className="mt-8 space-y-6 text-base leading-relaxed text-ink-800">
        <section>
          <h2 className="font-serif text-2xl">We stay in Gmail</h2>
          <p className="mt-2 text-ink-700">
            Tidimail is not a new inbox. Mail stays in your Gmail account. We ask Google for{" "}
            <code>gmail.modify</code> so we can label, archive, and move mail to Trash after you choose an action.
          </p>
        </section>
        <section>
          <h2 className="font-serif text-2xl">What we read</h2>
          <p className="mt-2 text-ink-700">
            During a scan we look at basic details only: sender name and address, subject, Gmail labels, date, a short
            snippet, and unsubscribe headers. We do not read the full email body. We do not send email as you, except a
            one-click unsubscribe when that header is present and you choose Unsubscribe.
          </p>
        </section>
        <section>
          <h2 className="font-serif text-2xl">What we store</h2>
          <p className="mt-2 text-ink-700">
            We store your Google account email, encrypted access tokens so daily scans can run, sender groups, those
            basic message details, your Keep / Digest / Unsubscribe choices, undo history, and (if you allow it) a push
            notification subscription. We do not store full message bodies or attachments.
          </p>
        </section>
        <section>
          <h2 className="font-serif text-2xl">Daily scan and notifications</h2>
          <p className="mt-2 text-ink-700">
            After your first review, Tidimail can scan again each day and notify you if new senders need a decision. You
            can ignore or turn off browser notifications at any time.
          </p>
        </section>
        <section>
          <h2 className="font-serif text-2xl">How to disconnect or delete</h2>
          <p className="mt-2 text-ink-700">
            In the app, open <Link href="/settings" className="underline">Account</Link>. Disconnect Gmail removes our
            access and deletes the mail data we stored. Delete account removes your Tidimail login as well. You can also
            revoke Tidimail in your{" "}
            <a className="underline" href="https://myaccount.google.com/permissions" target="_blank" rel="noreferrer">
              Google account permissions
            </a>
            .
          </p>
        </section>
        <section>
          <h2 className="font-serif text-2xl">Questions</h2>
          <p className="mt-2 text-ink-700">
            Email{" "}
            <a className="underline" href="mailto:tidimail.hello@gmail.com">
              tidimail.hello@gmail.com
            </a>
            . You can also write from the address you signed in with, or disconnect in{" "}
            <Link href="/settings" className="underline">
              Account
            </Link>{" "}
            if you want us to stop.
          </p>
        </section>
      </div>

      <p className="mt-10">
        <Link href="/" className="text-sm underline decoration-ink-900/20 underline-offset-4">
          Back home
        </Link>
      </p>
      <div className="mt-8">
        <SiteFooter />
      </div>
    </div>
  );
}
