"use client";

import { useEffect, useState } from "react";
import { usePathname, useRouter } from "next/navigation";
import { ConfirmDialog } from "@/components/ConfirmDialog";
import { enableCleanupNotifications } from "@/lib/push";
import { isDemo } from "@/lib/auth";
import { useSession } from "@/lib/session";

const seenKey = "tidimail.cleanup.seen";

export function CleanupAlert() {
  const pathname = usePathname();
  const router = useRouter();
  const { me, account } = useSession();
  const [open, setOpen] = useState(false);

  const alertAt = account?.cleanup_alert_at;
  const count = account?.cleanup_pending_count ?? me?.pending_senders ?? 0;
  const onboarding = pathname.startsWith("/onboarding");

  useEffect(() => {
    if (!me?.has_completed_first_sweep || onboarding || !alertAt || count < 1) {
      setOpen(false);
      return;
    }
    if (window.sessionStorage.getItem(seenKey) === alertAt) {
      setOpen(false);
      return;
    }
    setOpen(true);
  }, [alertAt, count, me?.has_completed_first_sweep, onboarding]);

  function dismiss() {
    if (alertAt) window.sessionStorage.setItem(seenKey, alertAt);
    setOpen(false);
  }

  return (
    <ConfirmDialog
      open={open}
      title="Daily scan is ready"
      body={
        count === 1 ? "1 sender to review." : `${count} senders to review.`
      }
      confirmLabel="Review now"
      cancelLabel="Later"
      onClose={dismiss}
      onConfirm={() => {
        dismiss();
        if (!isDemo()) void enableCleanupNotifications();
        router.push("/sweep");
      }}
    />
  );
}
