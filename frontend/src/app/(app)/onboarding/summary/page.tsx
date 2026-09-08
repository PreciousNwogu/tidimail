"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { ConfirmDialog } from "@/components/ConfirmDialog";
import { Stat } from "@/components/Stat";
import { api } from "@/lib/api";
import { addToSummary, writeSummary, emptySummary } from "@/lib/summary";
import { useToast } from "@/lib/toast";
import { useSession } from "@/lib/session";
import type { SweepResponse } from "@/lib/types";
import { asList } from "@/lib/types";

export default function SummaryPage() {
  const router = useRouter();
  const { push } = useToast();
  const { account, refresh } = useSession();
  const [sweep, setSweep] = useState<SweepResponse | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [confirm, setConfirm] = useState(false);
  const [busy, setBusy] = useState(false);
  const stillScanning = Boolean(
    account && account.sync_status !== "idle" && account.sync_status !== "failed",
  );

  useEffect(() => {
    let cancelled = false;

    async function load() {
      try {
        const next = await api.sweep();
        if (!cancelled) setSweep(next);
        await refresh();
      } catch (err) {
        if (!cancelled) setError(err instanceof Error ? err.message : "Could not load the sweep.");
      }
    }

    void load();
    if (!stillScanning) return;

    const timer = window.setInterval(() => void load(), 4000);
    return () => {
      cancelled = true;
      window.clearInterval(timer);
    };
  }, [refresh, stillScanning]);

  async function applyAll() {
    setBusy(true);
    try {
      const result = await api.applyRecommendations(["unsubscribe", "digest"]);
      const applied = asList(result.applied);
      writeSummary(emptySummary());
      addToSummary({
        archived: applied.reduce((sum, action) => sum + (action.type === "keep" ? 0 : action.message_count), 0),
        unsubscribed: applied.filter((action) => action.type === "unsubscribe").length,
        digested: applied.filter((action) => action.type === "digest").length,
        pending: (sweep?.stats.recommended_keep ?? 0),
      });
      push(`Applied ${result.applied_count} recommendations.`);
      router.push("/onboarding/done");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Could not apply recommendations.");
    } finally {
      setBusy(false);
      setConfirm(false);
    }
  }

  if (error && !sweep) {
    return <p className="text-clay-600">{error}</p>;
  }

  if (!sweep) {
    return <p className="text-sm text-ink-700">Counting senders…</p>;
  }

  const stats = sweep.stats;

  return (
    <div>
      <p className="kicker">First sweep</p>
      <h1 className="mt-3 max-w-2xl font-serif text-4xl tracking-tight">
        {stats.senders_total} senders. That’s the actual problem.
      </h1>
      {stillScanning ? (
        <p className="mt-4 text-sm text-ink-700">
          Still grouping the rest of your inbox
          {account?.sync_scanned_count
            ? ` — ${account.sync_scanned_count.toLocaleString()} emails so far.`
            : "."}
        </p>
      ) : null}
      <div className="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
        <Stat label="Senders found" value={stats.senders_total} />
        <Stat label="Emails scanned" value={stats.messages_scanned} />
        <Stat label="Suggested unsubscribe" value={stats.recommended_unsubscribe} />
        <Stat label="Suggested digest" value={stats.recommended_digest} />
        <Stat label="Suggested keep" value={stats.recommended_keep} />
        <Stat label="Quiet score" value={stats.quiet_score} />
      </div>
      {error ? <p className="mt-4 text-sm text-clay-600">{error}</p> : null}
      <div className="mt-8 flex flex-wrap gap-3">
        <button
          type="button"
          onClick={() => router.push("/onboarding/sweep")}
          className="rounded-full bg-ink-900 px-5 py-3 text-sm font-medium text-linen-50"
        >
          Start the sweep
        </button>
        <button
          type="button"
          onClick={() => setConfirm(true)}
          className="rounded-full border border-ink-900/15 bg-white px-5 py-3 text-sm font-medium"
        >
          Apply all unsubscribe + digest
        </button>
      </div>
      <ConfirmDialog
        open={confirm}
        title="Apply the noisy ones?"
        body="Digest and unsubscribe pending recommendations. Keep is left for you. Undo 24 hours."
        confirmLabel="Apply recommendations"
        busy={busy}
        onClose={() => setConfirm(false)}
        onConfirm={() => void applyAll()}
      />
    </div>
  );
}
