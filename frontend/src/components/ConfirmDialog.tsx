"use client";

export function ConfirmDialog({
  open,
  title,
  body,
  confirmLabel,
  cancelLabel = "Cancel",
  busy,
  onClose,
  onConfirm,
}: {
  open: boolean;
  title: string;
  body: string;
  confirmLabel: string;
  cancelLabel?: string;
  busy?: boolean;
  onClose: () => void;
  onConfirm: () => void;
}) {
  if (!open) return null;

  return (
    <div className="fixed inset-0 z-40 grid place-items-center bg-ink-900/30 p-4" role="dialog" aria-modal="true">
      <div className="w-full max-w-md rounded-3xl bg-linen-50 p-6 shadow-card">
        <h2 className="font-serif text-2xl tracking-tight">{title}</h2>
        <p className="mt-2 text-sm leading-relaxed text-ink-700">{body}</p>
        <div className="mt-6 flex justify-end gap-2">
          <button type="button" onClick={onClose} className="rounded-full px-4 py-2 text-sm text-ink-700">
            {cancelLabel}
          </button>
          <button
            type="button"
            disabled={busy}
            onClick={onConfirm}
            className="rounded-full bg-ink-900 px-4 py-2 text-sm font-medium text-linen-50 disabled:opacity-50"
          >
            {busy ? "Working…" : confirmLabel}
          </button>
        </div>
      </div>
    </div>
  );
}
