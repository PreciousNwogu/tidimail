"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { useParams } from "next/navigation";
import { CategoryChip } from "@/components/CategoryChip";
import { api } from "@/lib/api";
import { relativeTime } from "@/lib/format";
import { useReview } from "@/lib/use-review";
import type { ReviewAction, Sender } from "@/lib/types";

export default function SenderPassportPage() {
  const params = useParams<{ id: string }>();
  const id = Number(params.id);
  const [sender, setSender] = useState<Sender | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    setSender(await api.sender(id));
  }, [id]);

  const { review, pendingId } = useReview(() => load());

  useEffect(() => {
    if (!id) return;
    void load().catch((err) => setError(err instanceof Error ? err.message : "Could not load this sender."));
  }, [id, load]);

  async function onAction(action: ReviewAction) {
    if (!sender) return;
    await review(sender, action);
  }

  if (error) return <p className="text-clay-600">{error}</p>;
  if (!sender) return <p className="text-sm text-ink-700">Opening passport…</p>;

  return (
    <div className="mx-auto max-w-2xl space-y-8">
      <Link href="/senders" className="text-sm text-ink-700 underline-offset-4 hover:underline">
        All senders
      </Link>
      <header>
        <div className="flex flex-wrap items-center gap-2">
          <h1 className="font-serif text-4xl tracking-tight">{sender.name}</h1>
          <CategoryChip value={sender.status} />
        </div>
        <p className="mt-1 text-ink-700">
          {sender.email}
          {sender.reviewed_at ? ` · reviewed ${relativeTime(sender.reviewed_at)}` : ""}
        </p>
      </header>

      <section className="rounded-3xl bg-white/80 p-5 shadow-card">
        <p className="kicker">Recommendation</p>
        <p className="mt-2 font-serif text-2xl capitalize">{sender.recommendation}</p>
        <p className="mt-2 leading-relaxed text-ink-800">{sender.recommendation_reason}</p>
        <p className="mt-3 text-sm text-ink-700">
          Based on Gmail category, volume, unread ratio, and List-Unsubscribe — not a black box.
        </p>
      </section>

      <section className="grid gap-3 sm:grid-cols-2">
        <p className="rounded-2xl bg-white/70 px-4 py-3 text-sm">{sender.message_count} emails · {sender.unread_count} unread</p>
        <p className="rounded-2xl bg-white/70 px-4 py-3 text-sm">Last mail {relativeTime(sender.last_message_at)}</p>
        <p className="rounded-2xl bg-white/70 px-4 py-3 text-sm">First seen {relativeTime(sender.first_seen_at)}</p>
        <p className="rounded-2xl bg-white/70 px-4 py-3 text-sm">
          Unsubscribe header: {sender.has_list_unsubscribe ? "yes" : "no"}
        </p>
      </section>

      {(sender.gmail_categories ?? []).length > 0 ? (
        <p className="text-sm text-ink-700">Gmail categories: {sender.gmail_categories.join(", ").replace(/CATEGORY_/g, "").toLowerCase()}</p>
      ) : null}

      <section>
        <h2 className="font-serif text-2xl">Sample mail</h2>
        <ul className="mt-3 space-y-3">
          {(sender.messages ?? []).map((message) => (
            <li key={message.id} className="rounded-2xl border border-ink-900/8 bg-white/80 px-4 py-3">
              <p className="font-medium">{message.subject}</p>
              <p className="text-sm text-ink-700">{message.snippet}</p>
              <p className="mt-1 text-xs text-ink-700/80">
                {relativeTime(message.received_at)} · {message.is_read ? "read" : "unread"}
                {message.purged_at
                  ? " · in Gmail Trash"
                  : message.purge_at
                    ? ` · Gmail Trash ${relativeTime(message.purge_at)}`
                    : ""}
              </p>
            </li>
          ))}
        </ul>
      </section>

      <section className="space-y-3">
        <div className="flex flex-wrap items-center gap-3">
          <button
            type="button"
            disabled={pendingId === sender.id}
            onClick={() => void onAction("keep")}
            className="rounded-full border border-ink-900/10 bg-white px-4 py-2 text-sm"
          >
            Keep{sender.status === "digest" || sender.status === "unsubscribed" ? " (restore inbox)" : ""}
          </button>
          {sender.status !== "digest" ? (
            <button
              type="button"
              disabled={pendingId === sender.id}
              title="Filed out of the inbox. Moved to Gmail Trash after 30 days."
              onClick={() => void onAction("digest")}
              className="rounded-full bg-sage-600 px-4 py-2 text-sm text-white"
            >
              Digest
            </button>
          ) : null}
          {sender.status !== "unsubscribed" ? (
            <button
              type="button"
              disabled={pendingId === sender.id || !sender.has_list_unsubscribe}
              title={!sender.has_list_unsubscribe ? "No unsubscribe header — digest instead" : "Stop the sender. Existing mail is trashed after 30 days."}
              onClick={() => void onAction("unsubscribe")}
              className="rounded-full bg-clay-500 px-4 py-2 text-sm text-white disabled:opacity-40"
            >
              Unsubscribe
            </button>
          ) : (
            <p className="text-sm text-ink-700">Already unsubscribed — Keep restores mail without mailing them again.</p>
          )}
        </div>
      </section>

      {(sender.actions ?? []).length > 0 ? (
        <section>
          <h2 className="font-serif text-2xl">History</h2>
          <ul className="mt-3 space-y-2 text-sm text-ink-700">
            {sender.actions?.map((action) => (
              <li key={action.id}>
                {action.type} · {action.status}
                {action.proof?.ok === false ? " · proof failed" : action.proof?.ok ? " · proof ok" : ""}
                {action.can_undo ? " · undoable" : ""}
              </li>
            ))}
          </ul>
        </section>
      ) : null}
    </div>
  );
}
