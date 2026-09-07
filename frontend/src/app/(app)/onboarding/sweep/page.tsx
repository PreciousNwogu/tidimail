"use client";

import { useCallback, useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { SenderCard } from "@/components/SenderCard";
import { api } from "@/lib/api";
import { useReview } from "@/lib/use-review";
import { addToSummary } from "@/lib/summary";
import type { Sender, SweepResponse } from "@/lib/types";
import { asList } from "@/lib/types";

export default function OnboardingSweepPage() {
  const router = useRouter();
  const [sweep, setSweep] = useState<SweepResponse | null>(null);
  const [queue, setQueue] = useState<Sender[]>([]);
  const [skipped, setSkipped] = useState(0);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    const next = await api.sweep();
    setSweep(next);
    setQueue(asList(next.senders));
  }, []);

  const { review, pendingId } = useReview(() => load());

  useEffect(() => {
    void load().catch((err) => setError(err instanceof Error ? err.message : "Could not load senders."));
  }, [load]);

  const current = queue[0];
  const total = sweep?.stats.senders_pending ?? 0;
  const position = Math.max(1, (sweep?.stats.senders_total ?? 1) - total + 1 - skipped);

  function skip() {
    if (!current) return;
    setSkipped((n) => n + 1);
    setQueue((rows) => rows.slice(1));
  }

  if (error) return <p className="text-clay-600">{error}</p>;
  if (!sweep) return <p className="text-sm text-ink-700">Lining up senders…</p>;

  if (!current) {
    return (
      <div className="mx-auto max-w-lg py-12 text-center">
        <h1 className="font-serif text-4xl tracking-tight">That’s the stack.</h1>
        <p className="mt-3 text-ink-700">No pending senders left in this pass.</p>
        <button
          type="button"
          onClick={() => {
            addToSummary({ pending: skipped });
            router.push("/onboarding/done");
          }}
          className="mt-8 rounded-full bg-ink-900 px-5 py-3 text-sm font-medium text-linen-50"
        >
          See what changed
        </button>
      </div>
    );
  }

  return (
    <div className="mx-auto max-w-2xl">
      <div className="mb-6 flex items-end justify-between gap-4">
        <div>
          <p className="kicker">Sender stack</p>
          <h1 className="mt-2 font-serif text-3xl tracking-tight">One decision at a time.</h1>
        </div>
        <p className="text-sm text-ink-700">
          {Math.min(position, total || position)} of {Math.max(total, queue.length)} senders
        </p>
      </div>
      <SenderCard
        sender={current}
        pending={pendingId === current.id ? "keep" : null}
        onAction={(action) => void review(current, action)}
      />
      <div className="mt-4 flex justify-end">
        <button type="button" onClick={skip} className="text-sm text-ink-700 underline-offset-4 hover:underline">
          Skip for now
        </button>
      </div>
    </div>
  );
}
