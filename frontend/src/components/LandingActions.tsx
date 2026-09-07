"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { googleRedirectUrl } from "@/lib/api";
import { setToken } from "@/lib/auth";
import { resetDemoInbox } from "@/lib/demo";
import { useSession } from "@/lib/session";
import { emptySummary, writeSummary } from "@/lib/summary";
import { InstallApp } from "@/components/InstallApp";

const googleErrors: Record<string, string> = {
  google_not_configured:
    "Google sign-in is not set up yet. Add your Google client ID and secret to the backend .env, then try again.",
  google_failed: "Google sign-in didn’t finish. Try Sign in with Google again.",
  google_missing_gmail_scope:
    "Google signed you in, but Gmail access was not granted. Add the Gmail modify scope in Google Cloud, then sign in again and allow Gmail.",
};

export function LandingActions() {
  const router = useRouter();
  const { refresh } = useSession();
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const code = new URLSearchParams(window.location.search).get("error");
    if (code && googleErrors[code]) setError(googleErrors[code]);
  }, []);

  async function trySample() {
    resetDemoInbox();
    writeSummary(emptySummary());
    setToken("demo", "demo");
    await refresh();
    router.push("/onboarding/trust");
  }

  return (
    <div className="mt-8 space-y-3">
      {error ? <p className="max-w-md text-sm text-clay-600">{error}</p> : null}
      <div className="flex flex-wrap items-center gap-3">
        <a
          href={googleRedirectUrl()}
          className="inline-flex items-center gap-3 rounded-full border border-ink-900/10 bg-white px-4 py-2.5 text-sm font-medium text-ink-800 shadow-sm hover:border-ink-900/20"
        >
          <GoogleMark />
          Sign in with Google
        </a>
        <InstallApp variant="button" />
        <button
          type="button"
          onClick={() => void trySample()}
          className="rounded-full border border-ink-900/15 bg-white/70 px-5 py-2.5 text-sm font-medium text-ink-800 hover:border-sage-600"
        >
          Try a sample inbox
        </button>
      </div>
    </div>
  );
}

function GoogleMark() {
  return (
    <svg width="18" height="18" viewBox="0 0 18 18" aria-hidden="true">
      <path
        fill="#4285F4"
        d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z"
      />
      <path
        fill="#34A853"
        d="M9 18c2.43 0 4.467-.806 5.956-2.184l-2.908-2.258c-.806.54-1.837.86-3.048.86-2.344 0-4.328-1.584-5.036-3.711H.957v2.332C2.438 15.983 5.482 18 9 18z"
      />
      <path
        fill="#FBBC05"
        d="M3.964 10.707A5.41 5.41 0 0 1 3.682 9c0-.593.102-1.17.282-1.707V4.961H.957A8.996 8.996 0 0 0 0 9c0 1.452.348 2.827.957 4.039l3.007-2.332z"
      />
      <path
        fill="#EA4335"
        d="M9 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.463.891 11.426 0 9 0 5.482 0 2.438 2.017.957 4.961L3.964 7.293C4.672 5.163 6.656 3.58 9 3.58z"
      />
    </svg>
  );
}
