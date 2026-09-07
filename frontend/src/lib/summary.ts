export const APPLY_SUMMARY_KEY = "tidimail.applySummary";

export type ApplySummary = {
  archived: number;
  unsubscribed: number;
  digested: number;
  kept: number;
  pending: number;
};

export function emptySummary(): ApplySummary {
  return { archived: 0, unsubscribed: 0, digested: 0, kept: 0, pending: 0 };
}

export function readSummary(): ApplySummary {
  if (typeof window === "undefined") return emptySummary();
  try {
    return { ...emptySummary(), ...JSON.parse(window.sessionStorage.getItem(APPLY_SUMMARY_KEY) ?? "{}") };
  } catch {
    return emptySummary();
  }
}

export function writeSummary(summary: ApplySummary): void {
  window.sessionStorage.setItem(APPLY_SUMMARY_KEY, JSON.stringify(summary));
}

export function addToSummary(partial: Partial<ApplySummary>): ApplySummary {
  const next = { ...readSummary() };
  for (const [key, value] of Object.entries(partial) as [keyof ApplySummary, number | undefined][]) {
    if (typeof value === "number") next[key] += value;
  }
  writeSummary(next);
  return next;
}
