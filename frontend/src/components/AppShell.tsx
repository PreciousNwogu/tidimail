"use client";

import Link from "next/link";
import { usePathname, useRouter } from "next/navigation";
import { useEffect, useState } from "react";
import { api, googleRedirectUrl } from "@/lib/api";
import { useSession } from "@/lib/session";
import { QuietScore } from "@/components/QuietScore";
import { BrandLogo } from "@/components/BrandLogo";
import { InstallApp } from "@/components/InstallApp";
import { CleanupAlert } from "@/components/CleanupAlert";

const links = [
  { href: "/sweep", label: "Sweep" },
  { href: "/actions", label: "Undo" },
  { href: "/senders", label: "Senders" },
];

export function AppShell({ children }: { children: React.ReactNode }) {
  const pathname = usePathname();
  const router = useRouter();
  const { me, reconnect, refresh } = useSession();
  const [score, setScore] = useState<number | null>(null);
  const [status, setStatus] = useState<string | undefined>();

  useEffect(() => {
    void api
      .sweep()
      .then((sweep) => {
        setScore(sweep.stats.quiet_score);
        setStatus(
          sweep.stats.senders_pending === 0
            ? undefined
            : sweep.stats.senders_pending === 1
              ? "1 to review"
              : `${sweep.stats.senders_pending} to review`,
        );
      })
      .catch(() => undefined);
  }, [pathname]);

  async function logout() {
    await api.logout();
    await refresh();
    router.replace("/");
  }

  return (
    <div className="relative z-10 min-h-dvh">
      <header className="border-b border-ink-900/8 bg-linen-50/80 pt-[env(safe-area-inset-top)] backdrop-blur">
        <div className="mx-auto flex max-w-5xl items-center justify-between gap-4 px-5 py-4">
          <Link href={me?.has_completed_first_sweep ? "/sweep" : "/onboarding/summary"} className="flex items-center">
            <BrandLogo className="h-12 object-left sm:h-14" />
          </Link>
          <nav className="hidden items-center gap-6 text-sm text-ink-700 sm:flex">
            {links.map((link) => (
              <Link
                key={link.href}
                href={link.href}
                className={pathname.startsWith(link.href) ? "text-ink-900" : "hover:text-ink-900"}
              >
                {link.label}
              </Link>
            ))}
          </nav>
          <div className="flex items-center gap-4 text-sm">
            {typeof score === "number" ? <QuietScore score={score} extra={status} /> : null}
            <div className="flex items-center gap-3">
              <Link href="/install" className="hidden text-ink-700/70 hover:text-ink-900 lg:inline">
                Get the app
              </Link>
              <Link href="/settings" className="text-ink-700/70 hover:text-ink-900">
                Account
              </Link>
              <button type="button" onClick={() => void logout()} className="text-ink-700/70 hover:text-ink-900">
                Log out
              </button>
            </div>
          </div>
        </div>
        {reconnect ? (
          <div className="bg-clay-500/10 px-5 py-2 text-center text-sm text-clay-600">
            Gmail expired, tap to reconnect.{" "}
            <a className="font-medium underline" href={googleRedirectUrl()}>
              Reconnect Google
            </a>
          </div>
        ) : null}
      </header>
      <main className="mx-auto w-full max-w-5xl px-5 py-8 pb-28 sm:pb-8">
        <div className="mb-6 sm:hidden">
          <InstallApp />
        </div>
        {children}
        <CleanupAlert />
      </main>
      <nav className="fixed bottom-0 left-0 right-0 z-20 flex border-t border-ink-900/10 bg-linen-50/95 pb-[env(safe-area-inset-bottom)] sm:hidden">
        {links.map((link) => (
          <Link
            key={link.href}
            href={link.href}
            className={`flex min-h-12 flex-1 items-center justify-center py-3 text-center text-sm ${pathname.startsWith(link.href) ? "text-ink-900" : "text-ink-700"}`}
          >
            {link.label}
          </Link>
        ))}
      </nav>
    </div>
  );
}
