"use client";

import { useState } from "react";
import { api } from "@/lib/api";
import { addToSummary } from "@/lib/summary";
import { useToast } from "@/lib/toast";
import type { ReviewAction, ReviewOptions, Sender } from "@/lib/types";
import { asList } from "@/lib/types";

export function toastForReview(sender: Sender, action: ReviewAction, options?: ReviewOptions): string {
  if (options?.trashNow) return `Moved ${sender.name} to Gmail Trash.`;
  if (action === "keep") return `Kept ${sender.name}.`;
  if (action === "digest") return `Digested ${sender.name}.`;
  return `Unsubscribed ${sender.name}.`;
}

const BULK_CHUNK = 50;

export function useReview(onDone?: () => void | Promise<void>) {
  const { push } = useToast();
  const [pendingId, setPendingId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function review(sender: Sender, action: ReviewAction, options?: ReviewOptions) {
    setPendingId(sender.id);
    setError(null);
    try {
      const result = await api.review(sender.id, action, options);
      addToSummary({
        archived: action === "keep" ? 0 : sender.message_count,
        unsubscribed: action === "unsubscribe" ? 1 : 0,
        digested: action === "digest" ? 1 : 0,
        kept: action === "keep" ? 1 : 0,
      });
      push(toastForReview(sender, action, options), result.action);
      await onDone?.();
    } catch (err) {
      const message = err instanceof Error ? err.message : "Could not apply that action.";
      setError(message);
      push(message);
    } finally {
      setPendingId(null);
    }
  }

  async function reviewMany(
    ids: number[],
    action: ReviewAction,
    onProgress?: (message: string | null) => void,
    options?: ReviewOptions,
  ) {
    if (ids.length === 0) return;
    setError(null);
    const chunks: number[][] = [];
    for (let index = 0; index < ids.length; index += BULK_CHUNK) {
      chunks.push(ids.slice(index, index + BULK_CHUNK));
    }

    let appliedCount = 0;
    let failedCount = 0;

    try {
      for (let index = 0; index < chunks.length; index += 1) {
        const done = Math.min((index + 1) * BULK_CHUNK, ids.length);
        onProgress?.(`Working… ${done} of ${ids.length}`);
        const result = await api.reviewBulk(chunks[index], action, options);
        const applied = asList(result.applied);
        appliedCount += result.applied_count;
        failedCount += result.failed.length;
        addToSummary({
          archived: applied.reduce((sum, row) => sum + (row.type === "keep" ? 0 : row.message_count), 0),
          unsubscribed: applied.filter((row) => row.type === "unsubscribe").length,
          digested: applied.filter((row) => row.type === "digest").length,
          kept: applied.filter((row) => row.type === "keep").length,
        });
      }
      const labels: Record<ReviewAction, string> = {
        keep: "Keep",
        digest: "Digest",
        unsubscribe: "Unsubscribe",
      };
      const failedNote = failedCount ? ` ${failedCount} skipped.` : "";
      if (appliedCount === 0) {
        push(failedCount ? `Could not apply that action.${failedNote}` : "No matching senders.");
      } else {
        push(
          options?.trashNow
            ? `Trashed mail from ${appliedCount} senders.${failedNote}`
            : `${labels[action]} applied to ${appliedCount} senders.${failedNote}`,
        );
      }
      await onDone?.();
    } catch (err) {
      const message = err instanceof Error ? err.message : "Could not apply that action.";
      setError(message);
      push(message);
    }
  }

  return { review, reviewMany, pendingId, error };
}
