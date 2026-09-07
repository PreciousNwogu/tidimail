"use client";

import { SessionProvider } from "@/lib/session";
import { ToastProvider } from "@/lib/toast";
import { PwaRegister } from "@/components/PwaRegister";

export function Providers({ children }: { children: React.ReactNode }) {
  return (
    <SessionProvider>
      <ToastProvider>
        <PwaRegister />
        {children}
      </ToastProvider>
    </SessionProvider>
  );
}
