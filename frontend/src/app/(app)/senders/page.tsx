"use client";

import { useEffect, useMemo, useState } from "react";
import Link from "next/link";
import { CategoryChip } from "@/components/CategoryChip";
import { PendingReviewList } from "@/components/PendingReviewList";
import { api } from "@/lib/api";
import { plural } from "@/lib/format";
import type { Sender, SenderStatus } from "@/lib/types";

const filters: { id: SenderStatus | ""; label: string }[] = [
  { id: "", label: "All" },
  { id: "pending", label: "Pending" },
  { id: "keep", label: "Keep" },
  { id: "digest", label: "Digest" },
  { id: "unsubscribed", label: "Unsubscribed" },
];

export default function SendersPage() {
  const [status, setStatus] = useState<SenderStatus | "">("");
  const [q, setQ] = useState("");
  const [rows, setRows] = useState<Sender[]>([]);
  const [error, setError] = useState<string | null>(null);

  const query = useMemo(() => q.trim(), [q]);

  useEffect(() => {
    if (status === "pending") return;
    const handle = window.setTimeout(() => {
      void api
        .senders({ status: status || undefined, q: query || undefined })
        .then((payload) => setRows(payload.data ?? []))
        .catch((err) => setError(err instanceof Error ? err.message : "Could not load senders."));
    }, 200);
    return () => window.clearTimeout(handle);
  }, [status, query]);

  return (
    <div>
      <p className="kicker">Directory</p>
      <h1 className="mt-2 font-serif text-4xl tracking-tight">Senders</h1>
      <div className="mt-6 flex flex-wrap items-center gap-2">
        {filters.map((filter) => (
          <button
            key={filter.label}
            type="button"
            onClick={() => setStatus(filter.id)}
            className={`rounded-full px-3 py-1.5 text-sm ${status === filter.id ? "bg-ink-900 text-linen-50" : "bg-white text-ink-700"}`}
          >
            {filter.label}
          </button>
        ))}
        {status !== "pending" ? (
        <input
          value={q}
          onChange={(event) => setQ(event.target.value)}
          placeholder="Search name or domain"
          className="ml-auto w-full min-w-[12rem] rounded-full border border-ink-900/10 bg-white px-4 py-2 text-sm sm:w-64"
        />
        ) : null}
      </div>
      {error ? <p className="mt-4 text-clay-600">{error}</p> : null}
      {status === "pending" ? (
        <div className="mt-6">
          <PendingReviewList />
        </div>
      ) : (
        <ul className="mt-6 divide-y divide-ink-900/8 rounded-3xl border border-ink-900/8 bg-white/80">
          {rows.map((sender) => (
            <li key={sender.id}>
              <Link href={`/senders/${sender.id}`} className="flex items-center justify-between gap-4 px-5 py-4 hover:bg-linen-50">
                <div>
                  <p className="font-medium">{sender.name}</p>
                  <p className="text-sm text-ink-700">{sender.email}</p>
                </div>
                <div className="flex items-center gap-2">
                  <CategoryChip value={sender.status} />
                  <span className="text-sm text-ink-700">{plural(sender.message_count, "email", "emails")}</span>
                </div>
              </Link>
            </li>
          ))}
        </ul>
      )}
    </div>
  );
}
