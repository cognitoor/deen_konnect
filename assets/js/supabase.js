/**
 * supabase.js — a tiny Supabase client for DeenKonnect.
 * ----------------------------------------------------
 * Talks to the Supabase REST (PostgREST) endpoint with `fetch`. No SDK, no
 * bundler, no dependencies — the landing page only ever needs one INSERT.
 *
 * Credentials are never hardcoded. They arrive from config/config.php, which
 * reads SUPABASE_URL and SUPABASE_ANON_KEY from the environment, and are handed
 * to this module by app.js. The anon key is a public key: the `subscriptions`
 * table is protected by row level security so it can only ever accept inserts.
 */

/** Error thrown by the client, carrying a machine-readable `code`. */
export class SubscribeError extends Error {
  /**
   * @param {string} code  One of: not_configured | duplicate | rate_limited | network | server
   * @param {string} message
   */
  constructor(code, message) {
    super(message);
    this.name = 'SubscribeError';
    this.code = code;
  }
}

/**
 * Build a client bound to one project and table.
 *
 * @param {{url: string, key: string, table?: string}} options
 */
export function createClient({ url, key, table = 'subscriptions' }) {
  const configured = Boolean(url && key);
  const endpoint = configured ? `${url.replace(/\/+$/, '')}/rest/v1/${encodeURIComponent(table)}` : '';

  return {
    /** True when both SUPABASE_URL and SUPABASE_ANON_KEY were provided. */
    isConfigured: () => configured,

    /**
     * Insert one subscription row.
     *
     * `status` and `source` have database defaults, so we only send what we know.
     * A unique index on `email` turns a repeat signup into a 409, which we
     * translate into a friendly duplicate error rather than leaking whether an
     * address is already on the list through a lookup endpoint.
     *
     * @param {{email: string, ip_address?: string|null, user_agent?: string|null, source?: string}} record
     * @returns {Promise<void>}
     */
    async insertSubscription(record) {
      if (!configured) {
        throw new SubscribeError('not_configured', 'Supabase is not configured.');
      }

      let response;
      try {
        response = await fetch(endpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'apikey': key,
            'Authorization': `Bearer ${key}`,
            // Ask PostgREST not to echo the inserted row back.
            'Prefer': 'return=minimal',
          },
          body: JSON.stringify(record),
        });
      } catch (networkError) {
        throw new SubscribeError('network', 'The request never reached the server.');
      }

      if (response.ok) return;

      // 409 + Postgres 23505 = unique violation on the email column.
      if (response.status === 409) {
        throw new SubscribeError('duplicate', 'This address is already on the list.');
      }

      if (response.status === 429) {
        throw new SubscribeError('rate_limited', 'Too many requests.');
      }

      let detail = `${response.status} ${response.statusText}`;
      try {
        const body = await response.json();
        if (body && body.code === '23505') {
          throw new SubscribeError('duplicate', 'This address is already on the list.');
        }
        if (body && body.message) detail = body.message;
      } catch (parseError) {
        if (parseError instanceof SubscribeError) throw parseError;
        // Body was not JSON — fall through with the status line.
      }

      throw new SubscribeError('server', detail);
    },
  };
}
