"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { api } from "@/lib/api";
import { hoursLeft, relativeTime } from "@/lib/format";
import { useToast } from "@/lib/toast";
import type { InboxAction } from "@/lib/types";

function proofLabel(action: InboxAction): string | null {
  if (action.type !== "unsubscribe") return null;
  if (action.status === "queued") return "pending";
  if (action.status === "confirmed") return "confirmed";
  if (action.status === "failed") return "failed";
  return action.status;
}

export default function ActionsPage() {
  const { push } = useToast();
  const [rows, setRows] = useState<InboxAction[]>([]);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setRows(await api.actions());
  }, []);

  useEffect(() => {
    void load().catch((err) => setError(err instanceof Error ? err.message : "Could not load actions."));
  }, [load]);

  async function undo(action: InboxAction) {
    try {
      await api.undo(action.id);
      push(`Restored ${action.sender?.name ?? "that sender"}.`);
      await load();
    } catch (err) {
      push(err instanceof Error ? err.message : "Could not undo.");
    }
  }

  if (error) return <p className="text-clay-600">{error}</p>;

  return (
    <div>
      <p className="kicker">24 hours</p>
      <h1 className="mt-2 font-serif text-4xl tracking-tight">Undo</h1>
      <p className="mt-2 max-w-xl text-ink-700">
        Changed your mind? You have 24 hours to put that mail back in your inbox. Older items stay here as a record.
      </p>

      <div className="mt-8 space-y-3">
        {rows.length === 0 ? (
          <p className="rounded-2xl bg-white/70 px-5 py-8 text-sm text-ink-700">
            Nothing to undo yet. Review a sender first, then you can change your mind here.
          </p>
        ) : null}
        {rows.map((action) => (
          <article key={action.id} className="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-ink-900/8 bg-white/80 px-4 py-4">
            <div>
              <p className="font-medium capitalize">
                {action.type} · {action.sender?.name ?? "Sender"}
              </p>
              <p className="text-sm text-ink-700">
                {action.message_count} emails · {relativeTime(action.created_at)}
                {action.can_undo ? ` · ${hoursLeft(action.expires_at)}` : " · locked"}
                {proofLabel(action) ? ` · unsubscribe ${proofLabel(action)}` : ""}
                {action.auto_applied ? " · auto" : ""}
              </p>
            </div>
            <div className="flex items-center gap-3 text-sm">
              {action.sender ? (
                <Link href={`/senders/${action.sender.id}`} className="underline underline-offset-4">
                  Passport
                </Link>
              ) : null}
              {action.can_undo ? (
                <button type="button" onClick={() => void undo(action)} className="rounded-full bg-ink-900 px-3 py-1.5 text-linen-50">
                  Undo
                </button>
              ) : action.status === "failed" ? (
                <span className="text-clay-600">Failed — mail stays archived</span>
              ) : null}
            </div>
          </article>
        ))}
      </div>
    </div>
  );
}
