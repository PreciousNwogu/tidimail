"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { api } from "@/lib/api";
import { isDemo } from "@/lib/auth";
import { useSession } from "@/lib/session";

export default function TrustPage() {
  const router = useRouter();
  const { account, refresh } = useSession();
  const [error, setError] = useState<string | null>(null);
  const [busy, setBusy] = useState(false);

  async function startScan() {
    if (!account) {
      setError("No Gmail account is connected yet.");
      return;
    }
    setBusy(true);
    setError(null);
    try {
      if (!isDemo()) {
        await api.sync(account.id);
        await refresh();
      }
      router.push("/onboarding/scan");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Could not start a scan.");
      setBusy(false);
    }
  }

  return (
    <div className="mx-auto max-w-xl">
      <h1 className="font-serif text-4xl tracking-tight">Scan your inbox</h1>
      <p className="mt-4 text-ink-700">Keep, Digest, or Unsubscribe. Undo for 24 hours.</p>
      {error ? <p className="mt-4 text-sm text-clay-600">{error}</p> : null}
      <button
        type="button"
        onClick={() => void startScan()}
        disabled={busy}
        className="mt-8 rounded-full bg-ink-900 px-5 py-3 text-sm font-medium text-linen-50 disabled:opacity-50"
      >
        {busy ? "Starting…" : "Scan"}
      </button>
    </div>
  );
}
