export type ReviewAction = "keep" | "digest" | "unsubscribe";
export type ReviewOptions = { trashNow?: boolean };
export type SenderStatus = "pending" | "keep" | "digest" | "unsubscribed";
export type SenderCategory = "person" | "receipt" | "newsletter" | "promo" | "social" | "unknown";
export type SenderRecommendation = ReviewAction;
export type ActionStatus = "applied" | "queued" | "confirmed" | "failed" | "undone";

export type Account = {
  id: number;
  provider: string;
  email: string;
  name: string | null;
  last_synced_at: string | null;
  sync_status: "idle" | "queued" | "running" | "ready" | "failed" | string;
  sync_error: string | null;
  sync_scanned_count?: number;
  cleanup_pending_count?: number;
  cleanup_alert_at?: string | null;
};

export type User = {
  id: number;
  name: string;
  email: string;
  first_sweep_completed_at: string | null;
};

export type MeResponse = {
  user: User;
  accounts: Account[] | { data: Account[] };
  has_completed_first_sweep: boolean;
  pending_senders: number;
  purge_after_days?: number;
  vapid_public_key?: string;
  needs_gmail_reconnect?: boolean;
};

export type MailMessage = {
  id: number;
  gmail_id: string;
  subject: string | null;
  snippet: string | null;
  received_at: string | null;
  is_read: boolean;
  is_in_inbox: boolean;
  purge_at?: string | null;
  purged_at?: string | null;
};

export type UnsubscribeProof = {
  url?: string;
  one_click?: boolean;
  status?: number;
  ok?: boolean;
  body_snippet?: string;
};

export type InboxAction = {
  id: number;
  type: ReviewAction | null;
  status: ActionStatus | null;
  sender?: Sender | null;
  message_count: number;
  can_undo: boolean;
  expires_at: string | null;
  undone_at: string | null;
  proof: UnsubscribeProof | null;
  auto_applied: boolean;
  created_at: string | null;
  trashed_now?: boolean;
};

export type Sender = {
  id: number;
  account_id: number;
  email: string;
  name: string;
  domain: string | null;
  message_count: number;
  unread_count: number;
  first_seen_at: string | null;
  last_message_at: string | null;
  has_list_unsubscribe: boolean;
  category: SenderCategory | null;
  purpose?: SenderCategory | null;
  recommendation: SenderRecommendation | null;
  recommendation_reason: string | null;
  status: SenderStatus | null;
  reviewed_at: string | null;
  gmail_categories: string[];
  sample_subjects?: string[];
  messages?: MailMessage[];
  actions?: InboxAction[];
};

export type SweepStats = {
  quiet_score: number;
  senders_total: number;
  senders_pending: number;
  messages_scanned: number;
  messages_in_inbox: number;
  recommended_unsubscribe: number;
  recommended_digest: number;
  recommended_keep: number;
  needs_you: number;
  keep: number;
  digest: number;
  unsubscribed: number;
};

export type SweepResponse = {
  mode: "first_run" | "daily";
  stats: SweepStats;
  needs_you: Sender[] | { data: Sender[] };
  senders: Sender[] | { data: Sender[] };
};

export type ApplyResult = {
  applied: InboxAction[] | { data: InboxAction[] };
  applied_count: number;
  failed: { sender_id: number; error: string }[];
};

export type Paginated<T> = {
  data: T[];
  meta?: { current_page: number; last_page: number; total: number };
};

export type ApiError = {
  status: number;
  message: string;
  reconnect?: boolean;
};

export function asList<T>(value: T[] | { data: T[] } | undefined | null): T[] {
  if (!value) return [];
  if (Array.isArray(value)) return value;
  if (Array.isArray(value.data)) return value.data;
  return [];
}
