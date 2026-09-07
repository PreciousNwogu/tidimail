"use client";

import Link from "next/link";
import { BrandLogo } from "@/components/BrandLogo";
import { LandingActions } from "@/components/LandingActions";
import { InstallApp } from "@/components/InstallApp";
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
              Review first. Then you decide. Tidimail starts in{" "}
              <span className="font-medium text-ink-900">Review Mode</span>. We only look at basic email details like the
              sender, subject, labels, and unsubscribe information. We don&apos;t create a new inbox or change your
              emails during the review.
            </p>
            <p className="text-base leading-relaxed">
              Nothing is changed until <span className="font-medium text-ink-900">you choose an action</span>:
            </p>
            <ul className="space-y-1.5 text-base leading-relaxed">
              <li>
                <span className="font-medium text-ink-900">Keep</span> — Leave the sender&apos;s emails in your Gmail
                inbox.
              </li>
              <li>
                <span className="font-medium text-ink-900">Digest</span> — Move them aside so you can read them later.
              </li>
              <li>
                <span className="font-medium text-ink-900">Unsubscribe</span> — Stop receiving emails from the sender.
              </li>
            </ul>
            <p className="text-base leading-relaxed">
              You can <span className="font-medium text-ink-900">undo any decision within 24 hours</span>.
            </p>
            <p className="text-base leading-relaxed">
              After 30 days, emails from <span className="font-medium text-ink-900">Digest</span> and{" "}
              <span className="font-medium text-ink-900">Unsubscribe</span> senders are moved to Gmail Trash, helping
              keep your inbox clean and freeing up space.
            </p>
            <p className="text-base leading-relaxed">
              After your first review, Tidimail <span className="font-medium text-ink-900">scans every day on its own</span>.
              New mail from senders you already chose follows that choice. You only see new senders.
            </p>
            <p className="text-base font-medium leading-relaxed text-ink-900">You stay in control.</p>
          </div>
          <LandingActions />
          <InstallApp />
          <p className="mt-6 max-w-md text-xs leading-relaxed text-ink-700/80">
            We request <code>gmail.modify</code> so we can label and archive. We never send email as you except one-click unsubscribe.
          </p>
        </div>

        <div className="relative">
          <BrandLogo className="relative z-10 mx-auto mb-8 h-44 object-center sm:h-56 lg:h-64" />
          <div className="absolute -left-4 top-10 -z-10 hidden h-40 w-full rotate-[-6deg] rounded-3xl bg-clay-500/10 sm:block" />
          <div className="relative space-y-3">
            {[
              { name: "Northstar Shop", rec: "Unsubscribe", why: "14 emails, almost never opened." },
              { name: "The Margin", rec: "Digest", why: "Worth reading. Not worth the inbox." },
              { name: "Jordan Lee", rec: "Keep", why: "Looks like a person, not a list." },
            ].map((card, index) => (
              <div
                key={card.name}
                className="rounded-3xl border border-ink-900/8 bg-white/90 p-4 shadow-card"
                style={{ transform: `translateX(${index * 12}px)` }}
              >
                <p className="font-serif text-xl">{card.name}</p>
                <p className="mt-1 text-sm text-sage-700">{card.rec}</p>
                <p className="mt-1 text-sm text-ink-700">{card.why}</p>
              </div>
            ))}
          </div>
        </div>
      </div>

      <footer className="flex flex-wrap items-center gap-x-4 gap-y-2 pb-6 text-sm text-ink-700/70">
        <span>You stay in control.</span>
        <Link href="/sweep" className="underline decoration-ink-900/20 underline-offset-4">
          Already connected?
        </Link>
        <SiteFooter />
      </footer>
    </div>
  );
}
