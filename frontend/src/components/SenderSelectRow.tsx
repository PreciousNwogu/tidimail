"use client";

import Link from "next/link";
import { CategoryChip } from "@/components/CategoryChip";
import { ReviewButtons } from "@/components/ReviewButtons";
import { plural } from "@/lib/format";
import type { ReviewAction, ReviewOptions, Sender } from "@/lib/types";

export function SenderSelectRow({
  sender,
  selected,
  pending,
  showActions,
  highlight,
  onToggle,
  onAction,
}: {
  sender: Sender;
  selected: boolean;
  pending?: ReviewAction | null;
  showActions: boolean;
  highlight?: boolean;
  onToggle: () => void;
  onAction: (action: ReviewAction, options?: ReviewOptions) => void;
}) {
  return (
    <article
      className={`flex gap-3 rounded-2xl border bg-white/80 px-4 py-3 shadow-card ${
        selected ? "border-ink-900/25" : "border-ink-900/8"
      } ${highlight ? "ring-1 ring-gold-500/40" : ""}`}
    >
      <input
        type="checkbox"
        checked={selected}
        onChange={onToggle}
        className="mt-1.5 h-5 w-5 shrink-0 accent-ink-900"
        aria-label={`Select ${sender.name}`}
      />
      <div className="min-w-0 flex-1">
        <div className="flex flex-wrap items-center gap-2">
          <h2 className="truncate font-medium tracking-tight">{sender.name}</h2>
          <CategoryChip value={sender.category} />
          {highlight ? (
            <span className="rounded-full bg-gold-500/15 px-2 py-0.5 text-[11px] font-medium text-gold-500">
              Look first
            </span>
          ) : null}
        </div>
        <p className="truncate text-sm text-ink-700">
          {sender.email}
          {sender.domain ? ` · ${sender.domain}` : ""}
        </p>
        <p className="mt-1 text-sm text-ink-700">
          {plural(sender.message_count, "email", "emails")} · recommend {sender.recommendation}
        </p>
        {showActions ? (
          <div className="mt-3 flex flex-wrap items-center justify-between gap-3">
            <ReviewButtons sender={sender} pending={pending} onAction={onAction} />
            <Link href={`/senders/${sender.id}`} className="text-sm text-ink-700 underline underline-offset-4">
              Passport
            </Link>
          </div>
        ) : (
          <Link href={`/senders/${sender.id}`} className="mt-2 inline-block text-sm text-ink-700 underline underline-offset-4">
            Passport
          </Link>
        )}
      </div>
    </article>
  );
}
