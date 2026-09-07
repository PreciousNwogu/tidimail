import { clearAuth, getToken, googleRedirectUrl, isDemo } from "./auth";
import { friendlyError } from "./format";
import { demoApi } from "./demo";
import type {
  Account,
  ApplyResult,
  InboxAction,
  MeResponse,
  Paginated,
  ReviewAction,
  Sender,
  SweepResponse,
} from "./types";
import { asList } from "./types";

export { googleRedirectUrl } from "./auth";

const API_URL = (process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000").replace(/\/$/, "");

export class ApiRequestError extends Error {
  status: number;
  reconnect: boolean;

  constructor(status: number, message: string) {
    const cleaned = friendlyError(message);
    super(cleaned);
    this.status = status;
    this.reconnect = status === 401 || /reconnect google/i.test(cleaned);
  }
}

async function request<T>(path: string, init: RequestInit = {}): Promise<T> {
  const token = getToken();
  const headers = new Headers(init.headers);
  headers.set("Accept", "application/json");
  if (init.body && !headers.has("Content-Type")) {
    headers.set("Content-Type", "application/json");
  }
  if (token) headers.set("Authorization", `Bearer ${token}`);

  const response = await fetch(`${API_URL}${path}`, { ...init, headers });
  const payload = (await response.json().catch(() => ({}))) as {
    message?: string;
    errors?: Record<string, string[]>;
  };

  if (response.status === 401) {
    throw new ApiRequestError(401, payload.message ?? "Reconnect Google to continue.");
  }

  if (!response.ok) {
    const firstError = payload.errors ? Object.values(payload.errors)[0]?.[0] : undefined;
    const message = payload.message ?? payload.errors?.action?.[0] ?? firstError ?? "Something went wrong.";
    throw new ApiRequestError(response.status, message);
  }

  return payload as T;
}

export const api = {
  googleUrl: googleRedirectUrl,

  async me(): Promise<MeResponse> {
    if (isDemo()) return demoApi.me();
    return request<MeResponse>("/api/me");
  },

  async sync(accountId: number): Promise<Account> {
    if (isDemo()) return demoApi.sync();
    const payload = await request<{ account: Account }>(`/api/accounts/${accountId}/sync`, { method: "POST" });
    return payload.account;
  },

  async waitForSync(accountId: number, onTick?: (account: Account) => void): Promise<Account> {
    if (isDemo()) {
      const current = accountsFrom(demoApi.me())[0];
      if (current?.last_synced_at) return current;
      if (current) onTick?.({ ...current, sync_status: "running" });
      return demoApi.sync();
    }

    const started = Date.now();
    while (Date.now() - started < 180_000) {
      try {
        const me = await request<MeResponse>("/api/me");
        const account = asList(me.accounts).find((row) => row.id === accountId) ?? asList(me.accounts)[0];
        if (account) onTick?.(account);
        if (account?.sync_status === "failed" && !account.last_synced_at) {
          throw new ApiRequestError(502, account.sync_error ?? "We could not finish reading Gmail. Try again in a moment.");
        }
        if (account?.last_synced_at || account?.sync_status === "ready" || account?.sync_status === "idle") {
          return account;
        }
      } catch (err) {
        if (!(err instanceof ApiRequestError) || err.status !== 429) {
          throw err;
        }
      }
      await new Promise((resolve) => setTimeout(resolve, 3000));
    }

    throw new ApiRequestError(504, "Scan is taking longer than expected. Try again.");
  },

  async sweep(): Promise<SweepResponse> {
    if (isDemo()) return demoApi.sweep();
    return request<SweepResponse>("/api/sweep");
  },

  async completeSweep(): Promise<void> {
    if (isDemo()) {
      demoApi.completeSweep();
      return;
    }
    await request("/api/sweep/complete", { method: "POST" });
  },

  async applyRecommendations(actions: ReviewAction[] = ["unsubscribe", "digest"]): Promise<ApplyResult> {
    if (isDemo()) return demoApi.applyRecommendations(actions);
    return request<ApplyResult>("/api/sweep/apply-recommendations", {
      method: "POST",
      body: JSON.stringify({ actions }),
    });
  },

  async senders(params: { status?: string; q?: string } = {}): Promise<Paginated<Sender>> {
    if (isDemo()) return demoApi.senders(params.status, params.q);
    const query = new URLSearchParams();
    if (params.status) query.set("status", params.status);
    if (params.q) query.set("q", params.q);
    const suffix = query.toString() ? `?${query}` : "";
    return request<Paginated<Sender>>(`/api/senders${suffix}`);
  },

  async sender(id: number): Promise<Sender> {
    if (isDemo()) return demoApi.sender(id);
    return request<Sender>(`/api/senders/${id}`);
  },

  async review(id: number, action: ReviewAction): Promise<{ sender: Sender; action: InboxAction }> {
    if (isDemo()) return demoApi.review(id, action);
    return request(`/api/senders/${id}/review`, {
      method: "POST",
      body: JSON.stringify({ action }),
    });
  },

  async actions(): Promise<InboxAction[]> {
    if (isDemo()) return demoApi.actions();
    const payload = await request<InboxAction[] | { data: InboxAction[] }>("/api/actions");
    return asList(payload);
  },

  async undo(id: number): Promise<InboxAction> {
    if (isDemo()) return demoApi.undo(id);
    const payload = await request<{ action: InboxAction }>(`/api/actions/${id}/undo`, { method: "POST" });
    return payload.action;
  },

  async pushVapid(): Promise<{ public_key: string }> {
    if (isDemo()) return { public_key: "" };
    return request<{ public_key: string }>("/api/push/vapid");
  },

  async subscribePush(subscription: { endpoint: string; keys: { p256dh: string; auth: string } }): Promise<void> {
    if (isDemo()) return;
    await request("/api/push/subscribe", {
      method: "POST",
      body: JSON.stringify(subscription),
    });
  },

  async logout(): Promise<void> {
    if (!isDemo() && getToken()) {
      try {
        await request("/api/auth/logout", { method: "POST" });
      } catch {
        // still clear local session
      }
    }
    clearAuth();
  },

  async disconnect(): Promise<void> {
    if (!isDemo() && getToken()) {
      await request("/api/auth/disconnect", { method: "POST" });
    }
    clearAuth();
  },

  async deleteAccount(): Promise<void> {
    if (!isDemo() && getToken()) {
      await request("/api/me", { method: "DELETE" });
    }
    clearAuth();
  },
};

export function accountsFrom(me: MeResponse): Account[] {
  return asList(me.accounts);
}

export function postAuthPath(me: MeResponse): string {
  const account = accountsFrom(me)[0];
  if (!account?.last_synced_at) return "/onboarding/trust";
  if (!me.has_completed_first_sweep) return "/onboarding/summary";
  return "/sweep";
}
