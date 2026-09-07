"use client";

import type { ReviewAction, Sender } from "@/lib/types";

export function ReviewButtons({
  sender,
  pending,
  onAction,
}: {
  sender: Sender;
  pending?: ReviewAction | null;
  onAction: (action: ReviewAction) => void;
}) {
  const disabled = Boolean(pending);
  const unsubDisabled = disabled || !sender.has_list_unsubscribe;

  return (
    <div className="grid grid-cols-3 gap-2 sm:flex sm:flex-wrap">
      <button
        type="button"
        disabled={disabled}
        title="Stays in Gmail. Tidimail never moves Keep mail to Trash."
        onClick={() => onAction("keep")}
        className="min-h-11 rounded-full border border-ink-900/10 bg-white px-3 py-2.5 text-sm font-medium text-ink-800 hover:border-sage-600 disabled:opacity-50 sm:px-4"
      >
        Keep
      </button>
      <button
        type="button"
        disabled={disabled}
        title="Filed out of the inbox. Moved to Gmail Trash after 30 days."
        onClick={() => onAction("digest")}
        className="min-h-11 rounded-full bg-sage-600 px-3 py-2.5 text-sm font-medium text-white hover:bg-sage-700 disabled:opacity-50 sm:px-4"
      >
        Digest
      </button>
      <span className="relative inline-flex min-w-0" title={!sender.has_list_unsubscribe ? "No unsubscribe header — digest instead" : undefined}>
        <button
          type="button"
          disabled={unsubDisabled}
          onClick={() => onAction("unsubscribe")}
          title="Stop the sender. Existing mail is trashed after 30 days."
          className="min-h-11 w-full rounded-full bg-clay-500 px-2 py-2.5 text-sm font-medium text-white hover:bg-clay-600 disabled:cursor-not-allowed disabled:opacity-40 sm:px-4"
        >
          Unsubscribe
        </button>
      </span>
    </div>
  );
}
