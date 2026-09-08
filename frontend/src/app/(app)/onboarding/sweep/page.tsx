"use client";

import { useRouter } from "next/navigation";
import { PendingReviewList } from "@/components/PendingReviewList";
import { addToSummary } from "@/lib/summary";

export default function OnboardingSweepPage() {
  const router = useRouter();

  return (
    <div className="mx-auto max-w-2xl">
      <div className="mb-6">
        <p className="kicker">Sender stack</p>
        <h1 className="mt-2 font-serif text-3xl tracking-tight">Pick senders, then decide.</h1>
      </div>
      <PendingReviewList
        empty={
          <div className="py-8 text-center">
            <h2 className="font-serif text-3xl tracking-tight">That’s the stack.</h2>
            <button
              type="button"
              onClick={() => router.push("/onboarding/done")}
              className="mt-8 rounded-full bg-ink-900 px-5 py-3 text-sm font-medium text-linen-50"
            >
              See what changed
            </button>
          </div>
        }
      />
      <div className="mt-6 flex justify-end">
        <button
          type="button"
          onClick={() => {
            addToSummary({ pending: 0 });
            router.push("/onboarding/done");
          }}
          className="text-sm text-ink-700 underline-offset-4 hover:underline"
        >
          Done for now
        </button>
      </div>
    </div>
  );
}
