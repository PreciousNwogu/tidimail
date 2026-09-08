"use client";

export function QuietScore({
  score,
  extra,
}: {
  score: number;
  extra?: string;
}) {
  const clamped = Math.max(0, Math.min(100, score));
  const title = extra ? `Quiet score ${clamped}. ${extra}` : `Quiet score ${clamped}`;

  return (
    <div className="flex items-center gap-2" title={title}>
      <div
        className="grid h-10 w-10 shrink-0 place-items-center rounded-full border border-ink-900/10 bg-white text-sm font-semibold text-sage-700"
        aria-label={title}
      >
        {clamped}
      </div>
      {extra ? <p className="hidden text-sm text-ink-700 sm:block">{extra}</p> : null}
    </div>
  );
}
