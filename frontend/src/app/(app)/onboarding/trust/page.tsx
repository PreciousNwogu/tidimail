"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";
import { isDemo } from "@/lib/auth";
import { useSession } from "@/lib/session";

export default function TrustPage() {
  const router = useRouter();
  const { account, refresh } = useSession();
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function startScan() {
    if (!account) {
      setError("No Gmail account is connected yet.");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      if (!isDemo()) {
        await api.sync(account.id);
        await refresh();
      }
      router.push("/onboarding/scan");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Could not start a scan.");
      setBusy(false);
    }
  }

  return (
    <div className="mx-auto max-w-xl">
      <p className="kicker">Before we change anything</p>
      <h1 className="mt-3 font-serif text-4xl tracking-tight">Review first. Then you decide.</h1>
      <p className="mt-5 text-base leading-relaxed text-ink-700">
        Tidimail starts in <span className="font-medium text-ink-900">Review Mode</span>. We only look at basic email
        details like the sender, subject, labels, and unsubscribe information. We don&apos;t create a new inbox or
        change your emails during the review.
      </p>
      <p className="mt-4 text-base leading-relaxed text-ink-700">
        Nothing is changed until <span className="font-medium text-ink-900">you choose an action</span>:
      </p>
      <ul className="mt-4 space-y-3 text-ink-800">
        <li className="rounded-2xl bg-white/70 p-4">
          <span className="font-medium text-ink-900">Keep</span> — Leave the sender&apos;s emails in your Gmail inbox.
        </li>
        <li className="rounded-2xl bg-white/70 p-4">
          <span className="font-medium text-ink-900">Digest</span> — Move them aside so you can read them later.
        </li>
        <li className="rounded-2xl bg-white/70 p-4">
          <span className="font-medium text-ink-900">Unsubscribe</span> — Stop receiving emails from the sender.
        </li>
      </ul>
      <ul className="mt-4 space-y-3 text-ink-800">
        <li className="rounded-2xl bg-white/70 p-4">You can undo any decision within 24 hours.</li>
        <li className="rounded-2xl bg-white/70 p-4">
          After 30 days, emails from Digest and Unsubscribe senders are moved to Gmail Trash, helping keep your inbox
          clean and freeing up space.
        </li>
        <li className="rounded-2xl bg-white/70 p-4">
          After this first scan, Tidimail checks your mail every day on its own. You do not have to tap Scan again.
        </li>
        <li className="rounded-2xl bg-white/70 p-4 font-medium text-ink-900">You&apos;re always in control.</li>
      </ul>
      {error ? <p className="mt-4 text-sm text-clay-600">{error}</p> : null}
      <button
        type="button"
        onClick={() => void startScan()}
        disabled={busy}
        className="mt-8 rounded-full bg-ink-900 px-5 py-3 text-sm font-medium text-linen-50 disabled:opacity-50"
      >
        {busy ? "Starting…" : "Scan the last 30 days"}
      </button>
    </div>
  );
}
