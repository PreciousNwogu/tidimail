"use client";

import type { SenderCategory, SenderRecommendation, SenderStatus } from "@/lib/types";

const categoryStyles: Record<string, string> = {
  promo: "bg-clay-500/10 text-clay-600",
  newsletter: "bg-gold-500/15 text-gold-500",
  social: "bg-sage-50 text-sage-700",
  person: "bg-sage-600/10 text-sage-700",
  receipt: "bg-ink-900/5 text-ink-800",
  unknown: "bg-linen-200 text-ink-700",
};

export function CategoryChip({ value }: { value: SenderCategory | SenderRecommendation | SenderStatus | null }) {
  if (!value) return null;
  return (
    <span className={`inline-flex rounded-full px-2.5 py-0.5 text-[11px] font-medium capitalize ${categoryStyles[value] ?? "bg-linen-200 text-ink-700"}`}>
      {value}
    </span>
  );
}
