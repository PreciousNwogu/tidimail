"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { api, googleRedirectUrl } from "@/lib/api";
import { friendlyError, needsGoogleReconnect } from "@/lib/format";
import { useSession } from "@/lib/session";

export default function ScanPage() {
  const router = useRouter();
  const { account, refresh } = useSession();
  const [error, setError] = useState<string | null>(null);
  const [status, setStatus] = useState(account?.sync_status ?? "queued");
  const [scanned, setScanned] = useState(account?.sync_scanned_count ?? 0);

  useEffect(() => {
    if (!account) return;
    let cancelled = false;

    void (async () => {
      try {
        await api.waitForSync(account.id, (next) => {
          if (!cancelled) {
            setStatus(next.sync_status);
            setScanned(next.sync_scanned_count ?? 0);
          }
        });
        await refresh();
        if (!cancelled) router.replace("/onboarding/summary");
      } catch (err) {
        if (!cancelled) setError(friendlyError(err instanceof Error ? err.message : null, "Scan failed."));
      }
    })();

    return () => {
      cancelled = true;
    };
  }, [account, refresh, router]);

  return (
    <div className="mx-auto max-w-lg py-16 text-center">
      <p className="kicker">Scanning</p>
      <h1 className="mt-3 font-serif text-4xl tracking-tight">Finding the senders that congest your inbox.</h1>
      <div className="mx-auto mt-10 h-1.5 w-56 overflow-hidden rounded-full bg-linen-200">
        <div className="h-full w-1/2 animate-pulse rounded-full bg-sage-600" />
      </div>
      <p className="mt-4 text-sm text-ink-700">
        {status === "idle"
          ? "Finishing…"
          : status === "queued"
            ? "Starting the scan…"
            : status === "ready"
              ? "Ready to review — still scanning the rest in the background."
              : status === "running"
                ? scanned > 0
                  ? `Grouped ${scanned.toLocaleString()} recent emails…`
                  : "Reading promotions, newsletters, and social first…"
                : status}
      </p>
      <p className="mx-auto mt-2 max-w-sm text-sm text-ink-700/80">
        You stay on this screen until the last 30 days of inbox mail is grouped — sender, subject, labels, and
        unsubscribe details, never the full body.
      </p>
      {error ? (
        <div className="mt-6 space-y-3">
          <p className="text-sm text-clay-600">{error}</p>
          {needsGoogleReconnect(error) ? (
            <a
              href={googleRedirectUrl()}
              className="inline-flex rounded-full bg-ink-900 px-5 py-2.5 text-sm font-medium text-linen-50"
            >
              Reconnect Google
            </a>
          ) : (
            <button
              type="button"
              onClick={() => window.location.reload()}
              className="inline-flex rounded-full border border-ink-900/15 px-5 py-2.5 text-sm font-medium text-ink-800"
            >
              Try the scan again
            </button>
          )}
        </div>
      ) : null}
    </div>
  );
}
