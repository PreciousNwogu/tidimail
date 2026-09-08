import Link from "next/link";

export function SiteFooter() {
  return (
    <nav className="flex flex-wrap items-center gap-x-4 gap-y-2 pb-6 text-sm text-ink-700/70" aria-label="Legal">
      <Link href="/privacy" className="underline decoration-ink-900/20 underline-offset-4">
        Privacy
      </Link>
      <Link href="/terms" className="underline decoration-ink-900/20 underline-offset-4">
        Terms
      </Link>
      <Link href="/install" className="underline decoration-ink-900/20 underline-offset-4">
        Get the app
      </Link>
      <a href="mailto:tidimail.hello@gmail.com" className="underline decoration-ink-900/20 underline-offset-4">
        tidimail.hello@gmail.com
      </a>
    </nav>
  );
}
