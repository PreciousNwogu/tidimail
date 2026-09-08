"use client";

import Link from "next/link";
import { useRouter } from "next/navigation";
import { useState } from "react";
import { api } from "@/lib/api";
import { enableCleanupNotifications } from "@/lib/push";
import { isDemo } from "@/lib/auth";
import { readSummary } from "@/lib/summary";
import { useSession } from "@/lib/session";

export default function DonePage() {
  const router = useRouter();
  const { refresh } = useSession();
  const summary = readSummary();
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function finish() {
    setBusy(true);
    try {
      await api.completeSweep();
      if (!isDemo()) void enableCleanupNotifications();
      await refresh();
      router.replace("/sweep");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Could not finish first-run.");
      setBusy(false);
    }
  }

  return (
    <div className="mx-auto max-w-lg">
      <p className="kicker">Applied</p>
      <h1 className="mt-3 font-serif text-4xl tracking-tight">
        Archived {summary.archived} emails. Unsubscribed {summary.unsubscribed} senders.
        {summary.pending || summary.kept ? ` ${summary.kept + summary.pending} still need you.` : ""}
      </h1>
      <p className="mt-4 text-ink-700">Undo for 24 hours.</p>
      {error ? <p className="mt-4 text-sm text-clay-600">{error}</p> : null}
      <div className="mt-8 flex flex-wrap gap-3">
        <button
          type="button"
          disabled={busy}
          onClick={() => void finish()}
          className="rounded-full bg-ink-900 px-5 py-3 text-sm font-medium text-linen-50 disabled:opacity-50"
        >
          {busy ? "Saving…" : "That’s my daily sweep"}
        </button>
        <Link href="/actions" className="rounded-full border border-ink-900/15 px-5 py-3 text-sm">
          Undo
        </Link>
      </div>
    </div>
  );
}
