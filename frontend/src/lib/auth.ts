const TOKEN_KEY = "tidimail.token";
const MODE_KEY = "tidimail.mode";

export type AuthMode = "live" | "demo";

export function getToken(): string | null {
  if (typeof window === "undefined") return null;
  return window.localStorage.getItem(TOKEN_KEY);
}

export function setToken(token: string, mode: AuthMode = "live"): void {
  window.localStorage.setItem(TOKEN_KEY, token);
  window.localStorage.setItem(MODE_KEY, mode);
}

export function clearAuth(): void {
  window.localStorage.removeItem(TOKEN_KEY);
  window.localStorage.removeItem(MODE_KEY);
}

export function getMode(): AuthMode {
  if (typeof window === "undefined") return "live";
  return window.localStorage.getItem(MODE_KEY) === "demo" ? "demo" : "live";
}

export function isDemo(): boolean {
  return getMode() === "demo";
}

export function googleRedirectUrl(): string {
  const base = (process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8000").replace(/\/$/, "");
  return `${base}/auth/google/redirect`;
}
