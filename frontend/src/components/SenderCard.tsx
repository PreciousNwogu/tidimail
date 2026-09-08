"use client";

import Link from "next/link";
import { CategoryChip } from "@/components/CategoryChip";
import { ReviewButtons } from "@/components/ReviewButtons";
import { initials, plural, relativeTime } from "@/lib/format";
import type { ReviewAction, ReviewOptions, Sender } from "@/lib/types";

export function SenderCard({
  sender,
  pending,
  onAction,
}: {
  sender: Sender;
  pending?: ReviewAction | null;
  onAction: (action: ReviewAction, options?: ReviewOptions) => void;
}) {
  return (
    <article className="rounded-3xl border border-ink-900/8 bg-white/80 p-5 shadow-card">
      <div className="flex items-start gap-4">
        <div className="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-sage-50 font-serif text-lg text-sage-700">
          {initials(sender.name || sender.email)}
        </div>
        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-center gap-2">
            <h2 className="truncate font-serif text-2xl tracking-tight">{sender.name}</h2>
            <CategoryChip value={sender.category} />
          </div>
          <p className="truncate text-sm text-ink-700">
            {sender.email}
            {sender.domain ? ` · ${sender.domain}` : ""}
          </p>
          <p className="mt-2 text-sm text-ink-700">
            {plural(sender.message_count, "email", "emails")} · {plural(sender.unread_count, "unread", "unread")} · last mail{" "}
            {relativeTime(sender.last_message_at)}
          </p>
        </div>
      </div>

      <div className="mt-4 rounded-2xl bg-linen-100 px-4 py-3">
        <p className="kicker">Recommend {sender.recommendation}</p>
        <p className="mt-1 text-sm leading-relaxed text-ink-800">{sender.recommendation_reason}</p>
      </div>

      {(sender.sample_subjects ?? []).length > 0 ? (
        <ul className="mt-4 space-y-1 text-sm text-ink-700">
          {(sender.sample_subjects ?? []).slice(0, 2).map((subject) => (
            <li key={subject} className="truncate before:mr-2 before:text-ink-700/40 before:content-['“']">
              {subject}
            </li>
          ))}
        </ul>
      ) : null}

      <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
        <ReviewButtons sender={sender} pending={pending} onAction={onAction} />
        <Link href={`/senders/${sender.id}`} className="text-sm text-ink-700 underline decoration-ink-900/20 underline-offset-4">
          Open passport
        </Link>
      </div>
    </article>
  );
}
