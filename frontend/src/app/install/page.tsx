"use client";

import Link from "next/link";
import { BrandLogo } from "@/components/BrandLogo";
import { InstallApp } from "@/components/InstallApp";
import { SiteFooter } from "@/components/SiteFooter";

export default function InstallPage() {
  return (
    <div className="relative z-10 mx-auto min-h-dvh max-w-xl px-5 pb-[max(2rem,env(safe-area-inset-bottom))] pt-[max(2rem,env(safe-area-inset-top))]">
      <header className="flex items-center justify-between">
        <BrandLogo className="h-16 object-left" />
        <Link href="/" className="text-sm text-ink-700 underline decoration-ink-900/20 underline-offset-4">
          Back
        </Link>
      </header>

      <p className="kicker mt-12">Get the app</p>
      <h1 className="mt-3 font-serif text-4xl tracking-tight">Put Tidimail on your phone or computer.</h1>
      <p className="mt-4 text-base leading-relaxed text-ink-700">
        Tidimail is an installable app. It opens like any other app, but your mail stays in Gmail. After you review
        senders once, it scans every day in the background — you do not have to start a scan.
      </p>

      <InstallApp variant="hero" />

      <ol className="mt-8 space-y-3 text-ink-800">
        <li className="rounded-2xl bg-white/70 p-4">
          <span className="font-medium text-ink-900">Android or Windows (Chrome or Edge)</span>
          <p className="mt-1 text-sm text-ink-700">
            Open Tidimail in the browser, then tap Install / Add to home screen when it appears. Or use the browser menu
            → Install app.
          </p>
        </li>
        <li className="rounded-2xl bg-white/70 p-4">
          <span className="font-medium text-ink-900">iPhone or iPad</span>
          <p className="mt-1 text-sm text-ink-700">In Safari, tap Share, then Add to Home Screen.</p>
        </li>
        <li className="rounded-2xl bg-white/70 p-4">
          <span className="font-medium text-ink-900">Mac</span>
          <p className="mt-1 text-sm text-ink-700">In Chrome or Edge, open the browser menu and choose Install Tidimail.</p>
        </li>
      </ol>

      <p className="mt-6 text-sm text-ink-700/80">
        This is a home-screen app, not an App Store listing. Allow notifications when asked — after each daily scan
        Tidimail will pop up so you can come back and clean up. Daily scans still run even if the app is closed. Install
        and notifications need https:// on your real domain — localhost is only for you.
      </p>
      <div className="mt-10">
        <SiteFooter />
      </div>
    </div>
  );
}