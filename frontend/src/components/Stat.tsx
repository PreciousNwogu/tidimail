export function Stat({ label, value, hint }: { label: string; value: number | string; hint?: string }) {
  return (
    <div className="rounded-2xl border border-ink-900/8 bg-white/70 px-4 py-3">
      <p className="kicker">{label}</p>
      <p className="mt-1 font-serif text-3xl tracking-tight">{value}</p>
      {hint ? <p className="mt-1 text-sm leading-snug text-ink-700">{hint}</p> : null}
    </div>
  );
}
