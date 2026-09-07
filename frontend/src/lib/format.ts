export function initials(name: string): string {
  const parts = name.trim().split(/\s+/).filter(Boolean);
  if (parts.length === 0) return "?";
  if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
  return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
}

export function relativeTime(iso: string | null | undefined): string {
  if (!iso) return "—";
  const then = new Date(iso).getTime();
  if (Number.isNaN(then)) return "—";
  const delta = then - Date.now();
  const abs = Math.abs(delta);
  const minute = 60_000;
  const hour = 60 * minute;
  const day = 24 * hour;

  const rtf = new Intl.RelativeTimeFormat("en", { numeric: "auto" });
  if (abs < hour) return rtf.format(Math.round(delta / minute), "minute");
  if (abs < day) return rtf.format(Math.round(delta / hour), "hour");
  if (abs < 30 * day) return rtf.format(Math.round(delta / day), "day");
  return new Date(iso).toLocaleDateString(undefined, { month: "short", day: "numeric" });
}

export function hoursLeft(iso: string | null | undefined): string {
  if (!iso) return "";
  const ms = new Date(iso).getTime() - Date.now();
  if (ms <= 0) return "expired";
  const hours = Math.ceil(ms / 3_600_000);
  return hours === 1 ? "1 hour left" : `${hours} hours left`;
}

export function plural(n: number, one: string, many: string): string {
  return `${n} ${n === 1 ? one : many}`;
}

export function friendlyError(message: string | undefined | null, fallback = "Something went wrong."): string {
  if (!message) return fallback;
  const trimmed = message.trim();
  if (
    trimmed.startsWith("{") ||
    trimmed.startsWith("[") ||
    /"error"\s*:/.test(trimmed) ||
    /invalid_grant|insufficient authentication scopes/i.test(trimmed)
  ) {
    if (/reconnect google|unauthenticated|invalid_grant|401/i.test(trimmed)) {
      return "Gmail access expired. Reconnect Google to continue.";
    }
    return "We could not finish that. Try again in a moment.";
  }
  return trimmed;
}

export function needsGoogleReconnect(message: string | undefined | null): boolean {
  return Boolean(message && /reconnect google/i.test(message));
}
