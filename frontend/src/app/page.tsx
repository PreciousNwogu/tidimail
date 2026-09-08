"use client";

import Link from "next/link";
import { BrandLogo } from "@/components/BrandLogo";
import { DemoPlayer } from "@/components/DemoPlayer";
import { LandingActions } from "@/components/LandingActions";
import { SiteFooter } from "@/components/SiteFooter";

export default function LandingPage() {
  return (
    <div className="relative z-10 mx-auto flex min-h-dvh max-w-5xl flex-col px-5 pb-[max(1.5rem,env(safe-area-inset-bottom))] pt-[max(2rem,env(safe-area-inset-top))]">
      <header className="flex items-center justify-between">
        <BrandLogo className="h-20 object-left sm:h-24" />
        <p className="kicker">Stay in Gmail</p>
      </header>

      <div className="grid flex-1 items-center gap-12 py-16 lg:grid-cols-[1.1fr_0.9fr]">
        <div>
          <h1 className="max-w-xl font-serif text-4xl leading-[1.08] tracking-tight text-ink-900 sm:text-5xl">
            Use Tidimail to tidy Gmail — you stay in control.
          </h1>
          <div className="mt-6 max-w-lg space-y-4 text-ink-700">
            <p className="text-lg leading-relaxed">
              Review first. Nothing changes until you choose:
            </p>
            <ul className="space-y-1.5 text-base leading-relaxed">
              <li>
                <span className="font-medium text-ink-900">Keep</span> — stays in Gmail.
              </li>
              <li>
                <span className="font-medium text-ink-900">Digest</span> — filed aside, Trash after 30 days.
              </li>
              <li>
                <span className="font-medium text-ink-900">Unsubscribe</span> — stop the sender, Trash after 30 days.
              </li>
            </ul>
            <p className="text-base leading-relaxed">Undo for 24 hours. After the first review, Tidimail scans daily on its own.</p>
          </div>
          <LandingActions />
        </div>

        <DemoPlayer />
      </div>

      <footer className="flex flex-wrap items-center gap-x-4 gap-y-2 pb-6 text-sm text-ink-700/70">
        <Link href="/sweep" className="underline decoration-ink-900/20 underline-offset-4">
          Already connected?
        </Link>
        <SiteFooter />
      </footer>
    </div>
  );
}
