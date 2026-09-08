"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { PendingReviewList } from "@/components/PendingReviewList";
import { api } from "@/lib/api";
import { useSession } from "@/lib/session";
import type { SweepResponse } from "@/lib/types";
import { asList } from "@/lib/types";

export default function SweepPage() {
  const { refresh } = useSession();
  const [sweep, setSweep] = useState<SweepResponse | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    const next = await api.sweep();
    setSweep(next);
    await refresh();
  }, [refresh]);

  useEffect(() => {
    void load().catch((err) =>
      setError(err instanceof Error ? err.message : "Could not load today’s sweep."),
    );
  }, [load]);

  if (error) return <p className="text-clay-600">{error}</p>;
  if (!sweep) return <p className="text-sm text-ink-700">Checking the quiet…</p>;

  const needsYou = asList(sweep.needs_you);
  const pending = sweep.stats.senders_pending;

  return (
    <div className="space-y-10">
      <div>
        <p className="kicker">{sweep.mode === "first_run" ? "First run" : "Daily sweep"}</p>
        <h1 className="mt-2 font-serif text-4xl tracking-tight">Today’s decisions</h1>
      </div>

      {pending === 0 ? (
        <div className="rounded-3xl border border-dashed border-ink-900/15 bg-white/50 px-6 py-16 text-center">
          <h2 className="font-serif text-3xl">Inbox is quiet.</h2>
        </div>
      ) : (
        <section>
          <PendingReviewList pinned={needsYou} onChanged={load} />
        </section>
      )}

      <details className="rounded-2xl bg-white/60 px-5 py-4">
        <summary className="cursor-pointer text-sm font-medium">Already handled</summary>
        <p className="mt-3 text-sm text-ink-700">
          Keep {sweep.stats.keep} · Digest {sweep.stats.digest} · Unsubscribed {sweep.stats.unsubscribed}
        </p>
        <Link href="/senders" className="mt-2 inline-block text-sm underline">
          Browse senders
        </Link>
      </details>
    </div>
  );
}
