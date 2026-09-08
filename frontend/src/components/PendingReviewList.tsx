"use client";

import { useCallback, useEffect, useMemo, useRef, useState } from "react";
import { BulkReviewBar } from "@/components/BulkReviewBar";
import { SenderSelectRow } from "@/components/SenderSelectRow";
import { api } from "@/lib/api";
import type { ReviewAction, ReviewOptions, Sender } from "@/lib/types";
import { useReview } from "@/lib/use-review";

const PAGE_SIZE = 50;

export function PendingReviewList({
  pinned = [],
  onChanged,
  empty,
}: {
  pinned?: Sender[];
  onChanged?: () => void | Promise<void>;
  empty?: React.ReactNode;
}) {
  const pinnedIdKey = pinned.map((sender) => sender.id).join(",");
  const pinnedIds = useMemo(() => {
    return new Set(
      pinnedIdKey
        ? pinnedIdKey
            .split(",")
            .map((value) => Number(value))
            .filter((id) => Number.isFinite(id))
        : [],
    );
  }, [pinnedIdKey]);

  const [rows, setRows] = useState<Sender[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [error, setError] = useState<string | null>(null);
  const [selected, setSelected] = useState<Set<number>>(new Set());
  const [loadingMore, setLoadingMore] = useState(false);
  const [busy, setBusy] = useState(false);
  const [progress, setProgress] = useState<string | null>(null);
  const [loaded, setLoaded] = useState(false);

  const onChangedRef = useRef(onChanged);
  onChangedRef.current = onChanged;

  const reloadList = useCallback(async () => {
    const first = await api.senders({ status: "pending", per_page: PAGE_SIZE, page: 1 });
    const pending = (first.data ?? []).filter((sender) => !pinnedIds.has(sender.id));
    setRows(pending);
    setPage(1);
    setLastPage(first.meta?.last_page ?? 1);
    setTotal(first.meta?.total ?? pending.length);
    setSelected(new Set());
    setLoaded(true);
  }, [pinnedIds]);

  const finish = useCallback(async () => {
    await reloadList();
    await onChangedRef.current?.();
  }, [reloadList]);

  const { review, pendingId, reviewMany } = useReview(finish);

  useEffect(() => {
    void reloadList().catch((err) => setError(err instanceof Error ? err.message : "Could not load senders."));
  }, [reloadList]);

  const visible = useMemo(() => {
    const restIds = new Set(rows.map((sender) => sender.id));
    const head = pinned.filter((sender) => sender.status === "pending" && !restIds.has(sender.id));
    return [...head, ...rows];
  }, [pinned, rows]);

  const visibleIds = visible.map((sender) => sender.id);
  const allVisibleSelected = visibleIds.length > 0 && visibleIds.every((id) => selected.has(id));

  function toggle(id: number) {
    setSelected((current) => {
      const next = new Set(current);
      if (next.has(id)) next.delete(id);
      else next.add(id);
      return next;
    });
  }

  function selectVisible() {
    setSelected((current) => {
      if (allVisibleSelected) {
        const next = new Set(current);
        visibleIds.forEach((id) => next.delete(id));
        return next;
      }
      return new Set(Array.from(current).concat(visibleIds));
    });
  }

  async function selectAllPending() {
    const payload = await api.pendingIds();
    setSelected(new Set(payload.ids));
  }

  async function loadMore() {
    const nextPage = page + 1;
    setLoadingMore(true);
    try {
      const payload = await api.senders({ status: "pending", per_page: PAGE_SIZE, page: nextPage });
      const extra = (payload.data ?? []).filter((sender) => !pinnedIds.has(sender.id));
      setRows((current) => {
        const seen = new Set(current.map((sender) => sender.id));
        return [...current, ...extra.filter((sender) => !seen.has(sender.id))];
      });
      setPage(nextPage);
      setLastPage(payload.meta?.last_page ?? nextPage);
      setTotal(payload.meta?.total ?? total);
    } finally {
      setLoadingMore(false);
    }
  }

  async function applySelected(action: ReviewAction, options?: ReviewOptions) {
    const ids = Array.from(selected);
    if (ids.length === 0) return;
    setBusy(true);
    try {
      await reviewMany(ids, action, setProgress, options);
    } finally {
      setBusy(false);
      setProgress(null);
    }
  }

  if (error) return <p className="text-clay-600">{error}</p>;
  if (!loaded) return <p className="text-sm text-ink-700">Lining up senders…</p>;

  if (total === 0 && pinned.filter((sender) => sender.status === "pending").length === 0) {
    return empty ?? <p className="text-sm text-ink-700">No pending senders.</p>;
  }

  return (
    <div>
      <BulkReviewBar
        selectedCount={selected.size}
        visibleCount={visibleIds.length}
        totalCount={total}
        allVisibleSelected={allVisibleSelected}
        busy={busy}
        progress={progress}
        onSelectVisible={selectVisible}
        onSelectAll={() => void selectAllPending()}
        onClear={() => setSelected(new Set())}
        onApply={applySelected}
      />
      <div className="space-y-3">
        {visible.map((sender) => (
          <SenderSelectRow
            key={sender.id}
            sender={sender}
            selected={selected.has(sender.id)}
            pending={pendingId === sender.id ? sender.recommendation : null}
            showActions={selected.size === 0 && !busy}
            highlight={pinnedIds.has(sender.id)}
            onToggle={() => toggle(sender.id)}
            onAction={(action, options) => void review(sender, action, options)}
          />
        ))}
      </div>
      {page < lastPage ? (
        <button
          type="button"
          disabled={loadingMore}
          onClick={() => void loadMore()}
          className="mt-4 w-full rounded-full border border-ink-900/10 bg-white px-4 py-3 text-sm font-medium disabled:opacity-50"
        >
          {loadingMore ? "Loading…" : `Load more · ${visible.length} of ${total} pending`}
        </button>
      ) : null}
    </div>
  );
}
