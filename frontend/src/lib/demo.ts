import type {
  Account,
  ApplyResult,
  InboxAction,
  MailMessage,
  MeResponse,
  ReviewAction,
  ReviewOptions,
  Sender,
  SweepResponse,
} from "./types";
import { asList } from "./types";

const DEMO_KEY = "tidimail.demo.v2";

type DemoState = {
  scanned: boolean;
  completed: boolean;
  senders: Sender[];
  actions: InboxAction[];
  nextActionId: number;
};

function hoursAgo(hours: number): string {
  return new Date(Date.now() - hours * 3_600_000).toISOString();
}

function daysAgo(days: number): string {
  return new Date(Date.now() - days * 86_400_000).toISOString();
}

function seedSenders(): Sender[] {
  const messages = (
    senderId: number,
    rows: { subject: string; snippet: string; hours: number; unread?: boolean }[],
  ): MailMessage[] =>
    rows.map((row, index) => ({
      id: senderId * 10 + index,
      gmail_id: `demo-${senderId}-${index}`,
      subject: row.subject,
      snippet: row.snippet,
      received_at: hoursAgo(row.hours),
      is_read: row.unread === false,
      is_in_inbox: true,
    }));

  const base = (
    partial: Omit<Sender, "sample_subjects" | "messages" | "actions"> & { messages: MailMessage[] },
  ): Sender => ({
    ...partial,
    sample_subjects: partial.messages.map((message) => message.subject).filter(Boolean) as string[],
    actions: [],
  });

  return [
    base({
      id: 1,
      account_id: 1,
      email: "deals@northstar.shop",
      name: "Northstar Shop",
      domain: "northstar.shop",
      message_count: 14,
      unread_count: 13,
      first_seen_at: daysAgo(26),
      last_message_at: hoursAgo(11),
      has_list_unsubscribe: true,
      category: "promo",
      recommendation: "unsubscribe",
      recommendation_reason: "Promotional mail with an unsubscribe header; you rarely open it.",
      status: "pending",
      reviewed_at: null,
      gmail_categories: ["CATEGORY_PROMOTIONS"],
      messages: messages(1, [
        { subject: "Flash sale: 50% off everything", snippet: "Today only — extra 20% at checkout.", hours: 11 },
        { subject: "You left this in your cart", snippet: "The linen throw is still waiting.", hours: 48 },
        { subject: "Weekend drop is live", snippet: "New colors just landed.", hours: 80 },
      ]),
    }),
    base({
      id: 2,
      account_id: 1,
      email: "digest@themargin.substack.com",
      name: "The Margin",
      domain: "substack.com",
      message_count: 6,
      unread_count: 2,
      first_seen_at: daysAgo(21),
      last_message_at: hoursAgo(30),
      has_list_unsubscribe: true,
      category: "newsletter",
      recommendation: "digest",
      recommendation_reason: "Recurring mail you can read later instead of in the inbox.",
      status: "pending",
      reviewed_at: null,
      gmail_categories: ["CATEGORY_UPDATES"],
      messages: messages(2, [
        { subject: "Issue 48: Quiet software", snippet: "Tools that do less, on purpose.", hours: 30, unread: false },
        { subject: "Issue 47: Attention budgets", snippet: "What if email had a daily cap?", hours: 170 },
      ]),
    }),
    base({
      id: 3,
      account_id: 1,
      email: "jordan.lee@example.com",
      name: "Jordan Lee",
      domain: "example.com",
      message_count: 2,
      unread_count: 1,
      first_seen_at: daysAgo(4),
      last_message_at: hoursAgo(6),
      has_list_unsubscribe: false,
      category: "person",
      recommendation: "keep",
      recommendation_reason: "Looks like a person, not a list.",
      status: "pending",
      reviewed_at: null,
      gmail_categories: [],
      messages: messages(3, [
        { subject: "Thursday still work?", snippet: "I can do 3pm at the studio.", hours: 6 },
        { subject: "Notes from last week", snippet: "Attaching the sketch we liked.", hours: 90, unread: false },
      ]),
    }),
    base({
      id: 4,
      account_id: 1,
      email: "auto-confirm@amazon.com",
      name: "Amazon · receipts",
      domain: "amazon.com",
      message_count: 2,
      unread_count: 1,
      first_seen_at: daysAgo(18),
      last_message_at: hoursAgo(20),
      has_list_unsubscribe: false,
      category: "receipt",
      purpose: "receipt",
      recommendation: "keep",
      recommendation_reason: "This pile looks like receipts, invoices, or shipping — not ads. Other mail from this address is filed in a separate pile.",
      status: "pending",
      reviewed_at: null,
      gmail_categories: ["CATEGORY_UPDATES"],
      messages: messages(4, [
        { subject: "Your order has shipped", snippet: "Arriving Thursday by 8pm.", hours: 20, unread: false },
        { subject: "Order confirmation #114-883", snippet: "We received your order.", hours: 70, unread: false },
      ]),
    }),
    base({
      id: 9,
      account_id: 1,
      email: "auto-confirm@amazon.com",
      name: "Amazon · promotions",
      domain: "amazon.com",
      message_count: 8,
      unread_count: 8,
      first_seen_at: daysAgo(16),
      last_message_at: hoursAgo(5),
      has_list_unsubscribe: true,
      category: "promo",
      purpose: "promo",
      recommendation: "unsubscribe",
      recommendation_reason: "Promotional mail with an unsubscribe header; you rarely open it. Other mail from this address is filed in a separate pile.",
      status: "pending",
      reviewed_at: null,
      gmail_categories: ["CATEGORY_PROMOTIONS"],
      messages: messages(9, [
        { subject: "Prime Day: 40% off deals", snippet: "Lightning deals on headphones.", hours: 5 },
        { subject: "Your exclusive offer expires tonight", snippet: "Save on kitchen gear.", hours: 26 },
      ]),
    }),
    base({
      id: 5,
      account_id: 1,
      email: "no-reply@spotify.com",
      name: "Spotify",
      domain: "spotify.com",
      message_count: 9,
      unread_count: 9,
      first_seen_at: daysAgo(28),
      last_message_at: hoursAgo(8),
      has_list_unsubscribe: true,
      category: "promo",
      recommendation: "unsubscribe",
      recommendation_reason: "Promotional mail with an unsubscribe header; you rarely open it.",
      status: "pending",
      reviewed_at: null,
      gmail_categories: ["CATEGORY_PROMOTIONS"],
      messages: messages(5, [
        { subject: "Your 2026 Wrapped is ready", snippet: "A year of late-night playlists.", hours: 8 },
        { subject: "Premium, 3 months for $0.99", snippet: "Offer ends Sunday.", hours: 40 },
      ]),
    }),
    base({
      id: 6,
      account_id: 1,
      email: "notifications@linkedin.com",
      name: "LinkedIn",
      domain: "linkedin.com",
      message_count: 22,
      unread_count: 20,
      first_seen_at: daysAgo(29),
      last_message_at: hoursAgo(3),
      has_list_unsubscribe: true,
      category: "social",
      recommendation: "digest",
      recommendation_reason: "Social notifications are better as a digest than inbox noise.",
      status: "pending",
      reviewed_at: null,
      gmail_categories: ["CATEGORY_SOCIAL"],
      messages: messages(6, [
        { subject: "Jordan Lee viewed your profile", snippet: "See who else is looking.", hours: 3 },
        { subject: "You appeared in 12 searches", snippet: "Your profile is getting attention.", hours: 27 },
      ]),
    }),
    base({
      id: 7,
      account_id: 1,
      email: "statements@firstleaf.bank",
      name: "Firstleaf Bank",
      domain: "firstleaf.bank",
      message_count: 1,
      unread_count: 1,
      first_seen_at: daysAgo(2),
      last_message_at: hoursAgo(16),
      has_list_unsubscribe: false,
      category: "receipt",
      recommendation: "keep",
      recommendation_reason: "Looks like receipts or account mail you may need later.",
      status: "pending",
      reviewed_at: null,
      gmail_categories: [],
      messages: messages(7, [
        { subject: "Your August statement is ready", snippet: "Ending balance $4,812.19.", hours: 16 },
      ]),
    }),
    base({
      id: 8,
      account_id: 1,
      email: "hello@kitchen-notes.com",
      name: "Kitchen Notes",
      domain: "kitchen-notes.com",
      message_count: 4,
      unread_count: 1,
      first_seen_at: daysAgo(15),
      last_message_at: hoursAgo(52),
      has_list_unsubscribe: true,
      category: "newsletter",
      recommendation: "digest",
      recommendation_reason: "Recurring mail you can read later instead of in the inbox.",
      status: "pending",
      reviewed_at: null,
      gmail_categories: ["CATEGORY_UPDATES"],
      messages: messages(8, [
        { subject: "A soup for wet sidewalks", snippet: "Lentil, lemon, too much pepper.", hours: 52, unread: false },
        { subject: "The Tuesday loaf", snippet: "If you only bake one thing.", hours: 200, unread: false },
      ]),
    }),
  ];
}

const demoAccount = (): Account => ({
  id: 1,
  provider: "gmail",
  email: "ada@example.com",
  name: "Ada Lovelace",
  last_synced_at: read().scanned ? hoursAgo(0.01) : null,
  sync_status: read().scanned ? "idle" : "idle",
  sync_error: null,
});

function emptyState(): DemoState {
  return {
    scanned: false,
    completed: false,
    senders: seedSenders(),
    actions: [],
    nextActionId: 1,
  };
}

function read(): DemoState {
  if (typeof window === "undefined") return emptyState();
  const raw = window.localStorage.getItem(DEMO_KEY);
  if (!raw) return emptyState();
  try {
    return { ...emptyState(), ...JSON.parse(raw) };
  } catch {
    return emptyState();
  }
}

function write(state: DemoState): void {
  window.localStorage.setItem(DEMO_KEY, JSON.stringify(state));
}

export function resetDemoInbox(): void {
  write(emptyState());
}

function rank(sender: Sender): string {
  const priority =
    sender.recommendation === "unsubscribe" ? 1 : sender.recommendation === "digest" ? 2 : 3;
  return `${priority}-${String(1_000_000_000 - sender.message_count).padStart(10, "0")}`;
}

function computeSweep(state: DemoState): SweepResponse {
  const pending = [...state.senders].filter((sender) => sender.status === "pending").sort((a, b) => rank(a).localeCompare(rank(b)));
  const scanned = state.senders.reduce((sum, sender) => sum + sender.message_count, 0);
  const inInbox = state.senders.reduce((sum, sender) => {
    const messages = sender.messages ?? [];
    if (messages.length > 0) {
      return sum + messages.filter((message) => message.is_in_inbox).length;
    }
    return sum + (sender.status === "pending" || sender.status === "keep" ? sender.message_count : 0);
  }, 0);
  const needsYou = pending.filter(
    (sender) =>
      sender.recommendation === "keep" &&
      sender.unread_count > 0 &&
      sender.last_message_at &&
      Date.now() - new Date(sender.last_message_at).getTime() < 3 * 86_400_000,
  );

  return {
    mode: state.completed ? "daily" : "first_run",
    stats: {
      quiet_score: scanned === 0 ? 100 : Math.round(100 * (1 - inInbox / scanned)),
      senders_total: state.senders.length,
      senders_pending: pending.length,
      messages_scanned: scanned,
      messages_in_inbox: inInbox,
      recommended_unsubscribe: pending.filter((s) => s.recommendation === "unsubscribe").length,
      recommended_digest: pending.filter((s) => s.recommendation === "digest").length,
      recommended_keep: pending.filter((s) => s.recommendation === "keep").length,
      needs_you: needsYou.length,
      keep: state.senders.filter((s) => s.status === "keep").length,
      digest: state.senders.filter((s) => s.status === "digest").length,
      unsubscribed: state.senders.filter((s) => s.status === "unsubscribed").length,
    },
    needs_you: needsYou.slice(0, state.completed ? 8 : 20),
    senders: pending.slice(0, state.completed ? 8 : 20),
  };
}

function canUndo(action: InboxAction): boolean {
  if (action.status === "undone" || action.undone_at) return false;
  if (!action.expires_at) return true;
  return new Date(action.expires_at).getTime() > Date.now();
}

function applyAction(
  state: DemoState,
  sender: Sender,
  type: ReviewAction,
  options?: ReviewOptions,
): { sender: Sender; action: InboxAction } {
  if (type === "unsubscribe" && !sender.has_list_unsubscribe) {
    throw Object.assign(new Error("No unsubscribe header on this sender. Digest it instead."), { status: 422 });
  }

  const trashNow = Boolean(options?.trashNow) && type !== "keep";
  const nextStatus = type === "unsubscribe" ? "unsubscribed" : type;
  const now = new Date().toISOString();
  const updated: Sender = {
    ...sender,
    status: nextStatus,
    reviewed_at: now,
    messages: (sender.messages ?? []).map((message) => ({
      ...message,
      is_in_inbox: type === "keep",
      purge_at: type === "keep" || trashNow ? null : new Date(Date.now() + 30 * 86_400_000).toISOString(),
      purged_at: trashNow ? now : null,
    })),
  };

  const action: InboxAction = {
    id: state.nextActionId,
    type,
    status: type === "unsubscribe" ? "confirmed" : "applied",
    sender: { ...updated, messages: undefined, actions: undefined },
    message_count: sender.message_count,
    can_undo: true,
    expires_at: new Date(Date.now() + 24 * 3_600_000).toISOString(),
    undone_at: null,
    proof:
      type === "unsubscribe"
        ? { url: "https://example.com/unsub", one_click: true, status: 200, ok: true, body_snippet: "unsubscribed" }
        : null,
    auto_applied: false,
    created_at: now,
    trashed_now: trashNow,
  };

  return { sender: updated, action };
}

export const demoApi = {
  me(): MeResponse {
    const state = read();
    return {
      user: {
        id: 1,
        name: "Ada Lovelace",
        email: "ada@example.com",
        first_sweep_completed_at: state.completed ? hoursAgo(0.02) : null,
      },
      accounts: [demoAccount()],
      has_completed_first_sweep: state.completed,
      pending_senders: state.senders.filter((sender) => sender.status === "pending").length,
      purge_after_days: 30,
      needs_gmail_reconnect: false,
    };
  },

  async sync(): Promise<Account> {
    await new Promise((resolve) => setTimeout(resolve, 1600));
    const state = read();
    state.scanned = true;
    write(state);
    return demoAccount();
  },

  sweep(): SweepResponse {
    return computeSweep(read());
  },

  completeSweep(): void {
    const state = read();
    state.completed = true;
    write(state);
  },

  applyRecommendations(actions: ReviewAction[]): ApplyResult {
    const allowed = new Set(actions.length ? actions : ["unsubscribe", "digest"]);
    const state = read();
    const applied: InboxAction[] = [];
    const failed: ApplyResult["failed"] = [];

    state.senders = state.senders.map((sender) => {
      if (sender.status !== "pending" || !sender.recommendation || !allowed.has(sender.recommendation)) {
        return sender;
      }
      try {
        const result = applyAction(state, sender, sender.recommendation);
        state.nextActionId += 1;
        state.actions.unshift(result.action);
        applied.push(result.action);
        return result.sender;
      } catch (error) {
        failed.push({ sender_id: sender.id, error: error instanceof Error ? error.message : "Failed" });
        return sender;
      }
    });

    write(state);
    return { applied, applied_count: applied.length, failed };
  },

  senders(status?: string, q?: string, page = 1, perPage = 25): { data: Sender[]; meta: { current_page: number; last_page: number; total: number } } {
    let rows = read().senders;
    if (status) rows = rows.filter((sender) => sender.status === status);
    if (q) {
      const needle = q.toLowerCase();
      rows = rows.filter(
        (sender) =>
          sender.email.toLowerCase().includes(needle) ||
          sender.name.toLowerCase().includes(needle) ||
          (sender.domain ?? "").toLowerCase().includes(needle),
      );
    }
    const size = Math.max(1, perPage);
    const lastPage = Math.max(1, Math.ceil(rows.length / size));
    const current = Math.min(Math.max(1, page), lastPage);
    const start = (current - 1) * size;
    return {
      data: rows.slice(start, start + size),
      meta: { current_page: current, last_page: lastPage, total: rows.length },
    };
  },

  pendingIds(): { ids: number[]; total: number } {
    const ids = read()
      .senders.filter((sender) => sender.status === "pending")
      .map((sender) => sender.id);
    return { ids, total: ids.length };
  },

  sender(id: number): Sender {
    const state = read();
    const sender = state.senders.find((row) => row.id === id);
    if (!sender) throw Object.assign(new Error("Sender not found"), { status: 404 });
    return {
      ...sender,
      actions: state.actions.filter((action) => action.sender?.id === id),
    };
  },

  review(id: number, action: ReviewAction, options?: ReviewOptions): { sender: Sender; action: InboxAction } {
    const state = read();
    const current = state.senders.find((row) => row.id === id);
    if (!current) throw Object.assign(new Error("Sender not found"), { status: 404 });
    const result = applyAction(state, current, action, options);
    state.senders = state.senders.map((row) => (row.id === id ? result.sender : row));
    state.nextActionId += 1;
    state.actions.unshift(result.action);
    write(state);
    return result;
  },

  reviewBulk(ids: number[], action: ReviewAction, options?: ReviewOptions): ApplyResult {
    const state = read();
    const applied: InboxAction[] = [];
    const failed: ApplyResult["failed"] = [];

    for (const id of ids) {
      const current = state.senders.find((row) => row.id === id);
      if (!current || current.status !== "pending") {
        failed.push({ sender_id: id, error: "Already reviewed." });
        continue;
      }
      try {
        const type = action === "unsubscribe" && !current.has_list_unsubscribe ? "digest" : action;
        const result = applyAction(state, current, type, options);
        state.senders = state.senders.map((row) => (row.id === id ? result.sender : row));
        state.nextActionId += 1;
        state.actions.unshift(result.action);
        applied.push(result.action);
      } catch (error) {
        failed.push({ sender_id: id, error: error instanceof Error ? error.message : "Failed" });
      }
    }

    write(state);
    return { applied, applied_count: applied.length, failed };
  },

  actions(): InboxAction[] {
    return read().actions.map((action) => ({ ...action, can_undo: canUndo(action) }));
  },

  undo(id: number): InboxAction {
    const state = read();
    const action = state.actions.find((row) => row.id === id);
    if (!action || !canUndo(action)) {
      throw Object.assign(new Error("This action can no longer be undone."), { status: 422 });
    }
    const senderId = action.sender?.id;
    if (senderId) {
      state.senders = state.senders.map((sender) =>
        sender.id === senderId
          ? {
              ...sender,
              status: "pending",
              reviewed_at: null,
              messages: (sender.messages ?? []).map((message) => ({
                ...message,
                is_in_inbox: true,
                purge_at: null,
                purged_at: null,
              })),
            }
          : sender,
      );
    }
    const updated: InboxAction = {
      ...action,
      status: "undone",
      undone_at: new Date().toISOString(),
      can_undo: false,
    };
    state.actions = state.actions.map((row) => (row.id === id ? updated : row));
    write(state);
    return updated;
  },
};

export function demoMe(): MeResponse {
  return demoApi.me();
}

export { asList };
