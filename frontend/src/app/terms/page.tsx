import Link from "next/link";
import { BrandLogo } from "@/components/BrandLogo";
import { SiteFooter } from "@/components/SiteFooter";

export const metadata = {
  title: "Terms — Tidimail",
  description: "Simple terms for using Tidimail.",
};

export default function TermsPage() {
  return (
    <div className="relative z-10 mx-auto min-h-dvh max-w-2xl px-5 py-[max(2rem,env(safe-area-inset-top))]">
      <BrandLogo className="h-16 object-left" />
      <p className="kicker mt-10">Terms</p>
      <h1 className="mt-3 font-serif text-4xl tracking-tight">Use Tidimail to tidy Gmail — you stay in control.</h1>
      <p className="mt-4 text-sm text-ink-700">Last updated 7 September 2026.</p>

      <div className="mt-8 space-y-6 text-base leading-relaxed text-ink-800">
        <p className="text-ink-700">
          Tidimail helps you review senders and choose Keep, Digest, or Unsubscribe. Nothing leaves your inbox until you
          choose. You can undo a choice for 24 hours. Digest and Unsubscribe mail may be moved to Gmail Trash after 30
          days.
        </p>
        <p className="text-ink-700">
          You must use your own Gmail account. You are responsible for the choices you make. Tidimail is provided as-is
          while we grow. Please review senders before applying a batch of suggestions.
        </p>
        <p className="text-ink-700">
          By signing in you allow Tidimail to use Google access as described in the{" "}
          <Link href="/privacy" className="underline">
            Privacy
          </Link>{" "}
          page. You can disconnect at any time.
        </p>
      </div>

      <p className="mt-10">
        <Link href="/" className="text-sm underline decoration-ink-900/20 underline-offset-4">
          Back home
        </Link>
      </p>
      <div className="mt-8">
        <SiteFooter />
      </div>
    </div>
  );
}
