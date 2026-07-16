-- =============================================================================
-- DeenKonnect — subscriptions table
-- Run this in Supabase → SQL Editor → New query → Run.
-- =============================================================================

-- Enum keeps the status column honest.
do $$
begin
  if not exists (select 1 from pg_type where typname = 'subscription_status') then
    create type subscription_status as enum ('pending', 'confirmed', 'unsubscribed', 'bounced');
  end if;
end $$;

create table if not exists public.subscriptions (
  id          uuid primary key default gen_random_uuid(),
  email       text not null,
  created_at  timestamptz not null default now(),
  status      subscription_status not null default 'pending',
  ip_address  inet,
  user_agent  text,
  source      text not null default 'landing_page',

  -- Belt and braces: reject anything that isn't shaped like an email.
  constraint subscriptions_email_format check (email ~* '^[^@\s]+@[^@\s]+\.[^@\s]{2,}$'),
  constraint subscriptions_email_length check (char_length(email) between 5 and 254)
);

-- One row per address. This unique index is what turns a repeat signup into the
-- 409 that supabase.js reports as "already on the list".
create unique index if not exists subscriptions_email_key
  on public.subscriptions (lower(email));

create index if not exists subscriptions_created_at_idx
  on public.subscriptions (created_at desc);

-- Normalise the address before it is stored, so casing never creates a duplicate.
create or replace function public.normalise_subscription_email()
returns trigger
language plpgsql
as $$
begin
  new.email := lower(trim(new.email));
  return new;
end;
$$;

drop trigger if exists subscriptions_normalise_email on public.subscriptions;
create trigger subscriptions_normalise_email
  before insert or update on public.subscriptions
  for each row execute function public.normalise_subscription_email();


-- -----------------------------------------------------------------------------
-- Row level security
--
-- The anon key ships in the page, so the table must defend itself. Anonymous
-- visitors may INSERT and nothing else — no reading, updating or deleting the
-- list. The service_role key (server-side only) bypasses RLS entirely.
-- -----------------------------------------------------------------------------

alter table public.subscriptions enable row level security;

drop policy if exists "anon can join the waiting list" on public.subscriptions;
create policy "anon can join the waiting list"
  on public.subscriptions
  for insert
  to anon
  with check (
    source = 'landing_page'
    and status = 'pending'
  );

-- No select/update/delete policy exists for anon, so those are denied by default.

comment on table  public.subscriptions is 'Pre-launch waiting list captured from the DeenKonnect landing page.';
comment on column public.subscriptions.status is 'pending until the address is confirmed or unsubscribed.';
comment on column public.subscriptions.source is 'Where the signup came from. Landing page inserts are pinned to landing_page by RLS.';
