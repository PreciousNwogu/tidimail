"use client";

import { useEffect, useState, Suspense } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { api, postAuthPath } from "@/lib/api";
import { setToken } from "@/lib/auth";
import { useSession } from "@/lib/session";
import { BrandLogo } from "@/components/BrandLogo";

function CallbackInner() {
  const params = useSearchParams();
  const router = useRouter();
  const { refresh } = useSession();
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    const token = params.get("token");
    if (!token) {
      setError("Google did not return a session. Try connecting again.");
      return;
    }
    setToken(token, "live");
    void refresh()
      .then(async () => {
        const me = await api.me();
        router.replace(postAuthPath(me));
      })
      .catch((err) => {
        setError(err instanceof Error ? err.message : "Could not finish sign-in.");
      });
  }, [params, refresh, router]);

  return (
    <div className="grid min-h-screen place-items-center px-5 text-center">
      <div className="flex flex-col items-center">
        <BrandLogo className="mx-auto h-24 object-center sm:h-28" />
        <h1 className="mt-4 font-serif text-3xl">{error ? "Couldn’t connect" : "Finishing up…"}</h1>
        <p className="mt-2 text-sm text-ink-700">{error ?? "Storing your session and checking the inbox."}</p>
        {error ? (
          <a href="/" className="mt-6 inline-block text-sm underline">
            Back to start
          </a>
        ) : null}
      </div>
    </div>
  );
}

export default function AuthCallbackPage() {
  return (
    <Suspense fallback={<div className="grid min-h-screen place-items-center text-sm text-ink-700">Connecting…</div>}>
      <CallbackInner />
    </Suspense>
  );
}
