"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { isIos, isStandalone, promptInstall, subscribeInstallPrompt } from "@/lib/install-prompt";

export function InstallApp({
  variant = "card",
}: {
  variant?: "card" | "button" | "hero";
}) {
  const router = useRouter();
  const [standalone, setStandalone] = useState(false);
  const [ios, setIos] = useState(false);
  const [canPrompt, setCanPrompt] = useState(false);
  const [help, setHelp] = useState(false);

  useEffect(() => {
    setStandalone(isStandalone());
    setIos(isIos());
    return subscribeInstallPrompt((event) => setCanPrompt(Boolean(event)));
  }, []);

  if (standalone) {
    if (variant === "button") return null;
    return (
      <p className="mt-6 text-sm text-ink-700">Tidimail is installed on this device.</p>
    );
  }

  async function install() {
    const result = await promptInstall();
    if (result === "accepted") return;
    if (variant === "button") {
      router.push("/install");
      return;
    }
    setHelp(true);
  }

  const label = "Install Tidimail";
  const buttonClass =
    variant === "hero"
      ? "mt-6 inline-flex rounded-full bg-ink-900 px-6 py-3 text-sm font-medium text-linen-50"
      : variant === "button"
        ? "rounded-full bg-ink-900 px-5 py-2.5 text-sm font-medium text-linen-50"
        : "rounded-full bg-ink-900 px-4 py-2 text-sm font-medium text-linen-50";

  if (variant === "button") {
    return (
      <button type="button" onClick={() => void install()} className={buttonClass}>
        {label}
      </button>
    );
  }

  return (
    <div className={variant === "hero" ? "mt-6" : "mt-6 max-w-md rounded-2xl bg-sage-50 px-4 py-4 text-ink-800"}>
      <p className="text-sm leading-relaxed">
        {ios
          ? "On iPhone or iPad, tap Share, then Add to Home Screen."
          : "Install Tidimail on this device. It opens like an app. Your mail stays in Gmail."}
      </p>
      {!ios ? (
        <button type="button" onClick={() => void install()} className={buttonClass}>
          {label}
        </button>
      ) : (
        <p className="mt-3 text-sm font-medium text-ink-900">There is no Install button on iPhone — use Share → Add to Home Screen.</p>
      )}
      {help && !canPrompt ? (
        <p className="mt-3 text-sm text-ink-700">
          If nothing popped up, open the Chrome or Edge menu (three dots) and choose <span className="font-medium">Install Tidimail</span>
          , or click the install icon in the address bar.
        </p>
      ) : null}
    </div>
  );
}
