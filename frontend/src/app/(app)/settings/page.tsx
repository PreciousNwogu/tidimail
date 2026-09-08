"use client";

import { useState } from "react";
import { useRouter } from "next/navigation";
import { api, googleRedirectUrl } from "@/lib/api";
import { useSession } from "@/lib/session";
import { ConfirmDialog } from "@/components/ConfirmDialog";
import { SiteFooter } from "@/components/SiteFooter";

export default function SettingsPage() {
  const router = useRouter();
  const { me, account, refresh } = useSession();
  const [busy, setBusy] = useState<"disconnect" | "delete" | null>(null);
  const [confirm, setConfirm] = useState<"disconnect" | "delete" | null>(null);
  const [error, setError] = useState<string | null>(null);

  async function disconnect() {
    setBusy("disconnect");
    setError(null);
    try {
      await api.disconnect();
      await refresh();
      router.replace("/");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Could not disconnect.");
    } finally {
      setBusy(null);
      setConfirm(null);
    }
  }

  async function destroy() {
    setBusy("delete");
    setError(null);
    try {
      await api.deleteAccount();
      await refresh();
      router.replace("/");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Could not delete the account.");
    } finally {
      setBusy(null);
      setConfirm(null);
    }
  }

  return (
    <div className="mx-auto max-w-lg">
      <p className="kicker">Account</p>
      <h1 className="mt-3 font-serif text-4xl tracking-tight">Your Gmail connection.</h1>
      <p className="mt-3 text-base text-ink-700">
        {account?.email ?? me?.user.email}
      </p>

      {error ? <p className="mt-4 text-sm text-clay-600">{error}</p> : null}

      <div className="mt-8 space-y-3">
        <a
          href={googleRedirectUrl()}
          className="block rounded-2xl border border-ink-900/10 bg-white px-5 py-4 text-sm font-medium text-ink-900"
        >
          Reconnect Google
        </a>
        <button
          type="button"
          onClick={() => setConfirm("disconnect")}
          className="block w-full rounded-2xl border border-ink-900/10 bg-white px-5 py-4 text-left text-sm font-medium text-ink-900"
        >
          Disconnect Gmail
        </button>
        <button
          type="button"
          onClick={() => setConfirm("delete")}
          className="block w-full rounded-2xl border border-clay-500/30 bg-white px-5 py-4 text-left text-sm font-medium text-clay-600"
        >
          Delete my Tidimail account
        </button>
      </div>

      <div className="mt-10">
        <SiteFooter />
      </div>

      <ConfirmDialog
        open={confirm === "disconnect"}
        title="Disconnect Gmail?"
        body="Tidimail will lose access to this inbox and delete the sender data we stored. Your mail stays in Gmail."
        confirmLabel="Disconnect"
        busy={busy === "disconnect"}
        onClose={() => setConfirm(null)}
        onConfirm={() => void disconnect()}
      />
      <ConfirmDialog
        open={confirm === "delete"}
        title="Delete your Tidimail account?"
        body="This disconnects Gmail, deletes the data we stored, and removes your Tidimail login. Mail stays in Gmail."
        confirmLabel="Delete account"
        busy={busy === "delete"}
        onClose={() => setConfirm(null)}
        onConfirm={() => void destroy()}
      />
    </div>
  );
}
