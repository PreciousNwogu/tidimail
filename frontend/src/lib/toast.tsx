"use client";

import { createContext, useCallback, useContext, useMemo, useState } from "react";
import { api } from "@/lib/api";
import type { InboxAction } from "@/lib/types";

type Toast = {
  id: number;
  message: string;
  actionId?: number;
};

type ToastValue = {
  toasts: Toast[];
  push: (message: string, action?: InboxAction) => void;
  undo: (id: number) => Promise<void>;
  dismiss: (id: number) => void;
};

const ToastContext = createContext<ToastValue | null>(null);

export function ToastProvider({ children }: { children: React.ReactNode }) {
  const [toasts, setToasts] = useState<Toast[]>([]);

  const dismiss = useCallback((id: number) => {
    setToasts((current) => current.filter((toast) => toast.id !== id));
  }, []);

  const push = useCallback((message: string, action?: InboxAction) => {
    const id = Date.now() + Math.floor(Math.random() * 1000);
    setToasts((current) => [...current, { id, message, actionId: action?.id }]);
    window.setTimeout(() => dismiss(id), 7000);
  }, [dismiss]);

  const undo = useCallback(
    async (id: number) => {
      const toast = toasts.find((row) => row.id === id);
      if (!toast?.actionId) {
        dismiss(id);
        return;
      }
      await api.undo(toast.actionId);
      dismiss(id);
    },
    [dismiss, toasts],
  );

  const value = useMemo(() => ({ toasts, push, undo, dismiss }), [toasts, push, undo, dismiss]);

  return (
    <ToastContext.Provider value={value}>
      {children}
      <div className="pointer-events-none fixed bottom-6 left-1/2 z-50 flex w-[min(92vw,28rem)] -translate-x-1/2 flex-col gap-2">
        {toasts.map((toast) => (
          <div
            key={toast.id}
            className="pointer-events-auto flex items-center justify-between gap-4 rounded-2xl bg-ink-900 px-4 py-3 text-sm text-linen-50 shadow-card"
          >
            <p>{toast.message}</p>
            {toast.actionId ? (
              <button
                type="button"
                className="shrink-0 font-medium text-linen-200 underline decoration-linen-300/60 underline-offset-4"
                onClick={() => void undo(toast.id)}
              >
                Undo
              </button>
            ) : (
              <button type="button" className="text-linen-300" onClick={() => dismiss(toast.id)}>
                Dismiss
              </button>
            )}
          </div>
        ))}
      </div>
    </ToastContext.Provider>
  );
}

export function useToast(): ToastValue {
  const value = useContext(ToastContext);
  if (!value) throw new Error("useToast must be used within ToastProvider");
  return value;
}
