"use client";

import { useState } from "react";
import { api } from "@/lib/api";
import { addToSummary } from "@/lib/summary";
import { useToast } from "@/lib/toast";
import type { ReviewAction, Sender } from "@/lib/types";

export function toastForReview(sender: Sender, action: ReviewAction): string {
  if (action === "keep") return `Kept ${sender.name} in the inbox.`;
  if (action === "digest") return `Moved ${sender.message_count} emails from ${sender.name} to Digest. They go to Gmail Trash after 30 days.`;
  return `Unsubscribe sent for ${sender.name}. Existing mail goes to Gmail Trash after 30 days.`;
}

export function useReview(onDone?: () => void | Promise<void>) {
  const { push } = useToast();
  const [pendingId, setPendingId] = useState<number | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function review(sender: Sender, action: ReviewAction) {
    setPendingId(sender.id);
    setError(null);
    try {
      const result = await api.review(sender.id, action);
      addToSummary({
        archived: action === "keep" ? 0 : sender.message_count,
        unsubscribed: action === "unsubscribe" ? 1 : 0,
        digested: action === "digest" ? 1 : 0,
        kept: action === "keep" ? 1 : 0,
      });
      push(toastForReview(sender, action), result.action);
      await onDone?.();
    } catch (err) {
      const message = err instanceof Error ? err.message : "Could not apply that action.";
      setError(message);
      push(message);
    } finally {
      setPendingId(null);
    }
  }

  return { review, pendingId, error };
}
