import { resetDemoInbox } from "@/lib/demo";
import { setToken } from "@/lib/auth";
import { emptySummary, writeSummary } from "@/lib/summary";

export function beginDemo(): void {
  resetDemoInbox();
  writeSummary(emptySummary());
  setToken("demo", "demo");
}
