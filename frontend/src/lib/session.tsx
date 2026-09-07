"use client";

import { createContext, useCallback, useContext, useEffect, useMemo, useState } from "react";
import { api, postAuthPath } from "@/lib/api";
import { getToken, isDemo } from "@/lib/auth";
import type { Account, MeResponse } from "@/lib/types";
import { accountsFrom } from "@/lib/api";

type SessionValue = {
  ready: boolean;
  me: MeResponse | null;
  account: Account | null;
  error: string | null;
  reconnect: boolean;
  refresh: () => Promise<MeResponse | null>;
};

const SessionContext = createContext<SessionValue | null>(null);

export function SessionProvider({ children }: { children: React.ReactNode }) {
  const [ready, setReady] = useState(false);
  const [me, setMe] = useState<MeResponse | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [reconnect, setReconnect] = useState(false);

  const refresh = useCallback(async () => {
    if (!getToken() && !isDemo()) {
      setMe(null);
      setReady(true);
      return null;
    }
    try {
      const next = await api.me();
      setMe(next);
      setError(null);
      setReconnect(Boolean(next.needs_gmail_reconnect));
      setReady(true);
      return next;
    } catch (err) {
      const message = err instanceof Error ? err.message : "Could not load your session.";
      setError(message);
      setReconnect((err as { reconnect?: boolean })?.reconnect === true || (err as { status?: number })?.status === 401);
      setReady(true);
      return null;
    }
  }, []);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  const value = useMemo<SessionValue>(
    () => ({
      ready,
      me,
      account: me ? accountsFrom(me)[0] ?? null : null,
      error,
      reconnect,
      refresh,
    }),
    [ready, me, error, reconnect, refresh],
  );

  return <SessionContext.Provider value={value}>{children}</SessionContext.Provider>;
}

export function useSession(): SessionValue {
  const value = useContext(SessionContext);
  if (!value) throw new Error("useSession must be used within SessionProvider");
  return value;
}

export { postAuthPath };
