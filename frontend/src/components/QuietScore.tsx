"use client";

function meaning(score: number) {
  if (score >= 80) return "Your inbox is mostly clear of mail Tidimail already sorted.";
  if (score >= 50) return "Your inbox is getting quieter. Keep reviewing senders.";
  return "A lot of scanned mail is still sitting in your inbox.";
}

export function QuietScore({
  score,
  extra,
  size = "compact",
}: {
  score: number;
  extra?: string;
  size?: "compact" | "full";
}) {
  const clamped = Math.max(0, Math.min(100, score));
  const detail = `${clamped}% of mail Tidimail scanned is no longer in your inbox.`;
  const title = extra ? `${detail} ${extra}` : detail;

  if (size === "full") {
    return (
      <div className="flex max-w-md items-center gap-3">
        <div
          className="grid h-11 w-11 shrink-0 place-items-center rounded-full border border-ink-900/10 bg-white text-sm font-semibold text-sage-700"
          title={detail}
          aria-label={`Quiet score ${clamped}. ${detail}`}
        >
          {clamped}
        </div>
        <div className="max-w-sm">
          <p className="text-[11px] font-medium uppercase tracking-[0.18em] text-ink-700/70">Quiet score</p>
          <p className="text-sm leading-snug text-ink-800">{detail}</p>
          <p className="mt-1 text-sm text-ink-700">{meaning(clamped)}</p>
          {extra ? <p className="mt-1 text-sm text-ink-700">{extra}</p> : null}
        </div>
      </div>
    );
  }

  return (
    <div className="flex items-center gap-2" title={title}>
      <div
        className="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-ink-900/10 bg-white text-sm font-semibold text-sage-700"
        aria-label={`Quiet score ${clamped}. ${title}`}
      >
        {clamped}
      </div>
      {extra ? <p className="hidden text-sm text-ink-700 sm:block">{extra}</p> : null}
    </div>
  );
}
