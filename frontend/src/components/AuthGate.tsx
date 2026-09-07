"use client";

import { useEffect } from "react";
import { useRouter } from "next/navigation";
import { getToken } from "@/lib/auth";
import { useSession } from "@/lib/session";

export function AuthGate({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const { ready, me, refresh } = useSession();

  useEffect(() => {
    if (!ready) return;
    if (getToken() && !me) {
      void refresh();
      return;
    }
    if (!getToken() && !me) router.replace("/");
  }, [ready, me, router, refresh]);

  if (!ready || (getToken() && !me)) {
    return (
      <div className="grid min-h-screen place-items-center text-sm text-ink-700">
        Opening your sweep…
      </div>
    );
  }

  if (!me) return null;
  return <>{children}</>;
}
