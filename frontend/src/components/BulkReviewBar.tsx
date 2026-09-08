"use client";

import { useState } from "react";
import { ConfirmDialog } from "@/components/ConfirmDialog";
import { plural } from "@/lib/format";
import type { ReviewAction, ReviewOptions } from "@/lib/types";

type BulkConfirm = ReviewAction | "trash_now";

const copy: Record<BulkConfirm, { title: string; body: (count: number) => string; confirm: string }> = {
  keep: {
    title: "Keep these senders?",
    body: (count) => `${plural(count, "sender", "senders")} stay in Gmail.`,
    confirm: "Keep selected",
  },
  digest: {
    title: "Digest these senders?",
    body: (count) => `${plural(count, "sender", "senders")} leave the inbox. Trash after 30 days. Undo 24 hours.`,
    confirm: "Digest selected",
  },
  unsubscribe: {
    title: "Unsubscribe these senders?",
    body: (count) =>
      `Stop ${plural(count, "sender", "senders")}. Trash after 30 days. No unsubscribe header → Digest. Undo 24 hours.`,
    confirm: "Unsubscribe selected",
  },
  trash_now: {
    title: "Trash now?",
    body: (count) =>
      `Move mail from ${plural(count, "sender", "senders")} to Gmail Trash now. No unsubscribe header → Digest. Undo 24 hours.`,
    confirm: "Trash now",
  },
};

export function BulkReviewBar({
  selectedCount,
  visibleCount,
  totalCount,
  allVisibleSelected,
  busy,
  progress,
  onSelectVisible,
  onSelectAll,
  onClear,
  onApply,
}: {
  selectedCount: number;
  visibleCount: number;
  totalCount: number;
  allVisibleSelected: boolean;
  busy: boolean;
  progress?: string | null;
  onSelectVisible: () => void;
  onSelectAll: () => void;
  onClear: () => void;
  onApply: (action: ReviewAction, options?: ReviewOptions) => Promise<void> | void;
}) {
  const [confirm, setConfirm] = useState<BulkConfirm | null>(null);

  return (
    <>
      <div className="sticky top-0 z-10 -mx-1 mb-4 rounded-2xl border border-ink-900/10 bg-linen-50/95 px-3 py-3 backdrop-blur">
        <div className="flex flex-wrap items-center gap-2">
          <label className="inline-flex items-center gap-2 text-sm font-medium">
            <input
              type="checkbox"
              checked={allVisibleSelected && visibleCount > 0}
              onChange={onSelectVisible}
              className="h-5 w-5 accent-ink-900"
            />
            {totalCount > visibleCount ? "Select page" : "Select all"}
          </label>
          {totalCount > visibleCount ? (
            <button
              type="button"
              onClick={onSelectAll}
              className="rounded-full border border-ink-900/10 bg-white px-3 py-1.5 text-sm font-medium"
            >
              Select all {totalCount}
            </button>
          ) : null}
          {selectedCount > 0 ? (
            <button type="button" onClick={onClear} className="text-sm text-ink-700 underline-offset-4 hover:underline">
              Clear
            </button>
          ) : null}
          {progress ? (
            <p className="ml-auto text-sm text-ink-700">{progress}</p>
          ) : selectedCount > 0 ? (
            <p className="ml-auto text-sm text-ink-700">{plural(selectedCount, "sender", "senders")} selected</p>
          ) : null}
        </div>
        {selectedCount > 0 ? (
          <div className="mt-3 grid grid-cols-3 gap-2">
            <button
              type="button"
              disabled={busy}
              onClick={() => setConfirm("keep")}
              className="min-h-11 rounded-full border border-ink-900/10 bg-white px-3 py-2 text-sm font-medium disabled:opacity-50"
            >
              Keep
            </button>
            <button
              type="button"
              disabled={busy}
              onClick={() => setConfirm("digest")}
              className="min-h-11 rounded-full bg-sage-600 px-3 py-2 text-sm font-medium text-white disabled:opacity-50"
            >
              Digest
            </button>
            <button
              type="button"
              disabled={busy}
              onClick={() => setConfirm("unsubscribe")}
              className="min-h-11 rounded-full bg-clay-500 px-3 py-2 text-sm font-medium text-white disabled:opacity-50"
            >
              Unsubscribe
            </button>
            <button
              type="button"
              disabled={busy}
              onClick={() => setConfirm("trash_now")}
              className="col-span-3 min-h-11 rounded-full border border-clay-500/40 bg-white px-3 py-2 text-sm font-medium text-clay-600 disabled:opacity-50"
            >
              Trash now
            </button>
          </div>
        ) : null}
      </div>
      <ConfirmDialog
        open={Boolean(confirm)}
        title={confirm ? copy[confirm].title : ""}
        body={confirm ? copy[confirm].body(selectedCount) : ""}
        confirmLabel={confirm ? copy[confirm].confirm : "Apply"}
        busy={busy}
        onClose={() => setConfirm(null)}
        onConfirm={() => {
          if (!confirm) return;
          const next = confirm;
          const action: ReviewAction = next === "trash_now" ? "unsubscribe" : next;
          const options: ReviewOptions | undefined = next === "trash_now" ? { trashNow: true } : undefined;
          void Promise.resolve(onApply(action, options)).finally(() => setConfirm(null));
        }}
      />
    </>
  );
}
