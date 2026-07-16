/**
 * app.js — DeenKonnect landing page behaviour.
 * -------------------------------------------
 *  · reads the public config injected by index.php
 *  · reveals sections on scroll
 *  · runs the launch countdown
 *  · validates and submits the email subscription forms
 *
 * Loaded as a module, so it is deferred by default and never blocks paint.
 */

import { createClient, SubscribeError } from './supabase.js';

/* -------------------------------------------------------------------------- */
/* Config                                                                     */
/* -------------------------------------------------------------------------- */

/** Read the JSON config block. Returns an empty object if it is missing. */
function readConfig() {
  const node = document.getElementById('dk-config');
  if (!node) return {};
  try {
    return JSON.parse(node.textContent);
  } catch {
    console.warn('DeenKonnect: config block could not be parsed.');
    return {};
  }
}

const config = readConfig();

const supabase = createClient({
  url: config.supabaseUrl,
  key: config.supabaseKey,
  table: config.table,
});

/** True when the visitor asked for less animation. */
const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

// Marks that JS is running, so CSS can safely hide elements before revealing them.
document.documentElement.classList.add('js');

/* -------------------------------------------------------------------------- */
/* Scroll reveals + sticky header                                             */
/* -------------------------------------------------------------------------- */

function initReveals() {
  const items = document.querySelectorAll('.reveal');

  // No IntersectionObserver, or motion is unwelcome: show everything at once.
  if (prefersReducedMotion || !('IntersectionObserver' in window)) {
    items.forEach((item) => item.classList.add('is-revealed'));
    return;
  }

  const observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry, index) => {
        if (!entry.isIntersecting) return;
        // Stagger siblings slightly so a grid arrives as a sequence, not a flash.
        entry.target.style.animationDelay = `${Math.min(index * 80, 320)}ms`;
        entry.target.classList.add('is-revealed');
        observer.unobserve(entry.target);
      });
    },
    { rootMargin: '0px 0px -8% 0px', threshold: 0.15 }
  );

  items.forEach((item) => observer.observe(item));
}

function initStickyHeader() {
  const header = document.querySelector('.site-header');
  if (!header) return;

  const sentinel = document.createElement('div');
  sentinel.setAttribute('aria-hidden', 'true');
  document.body.prepend(sentinel);

  if (!('IntersectionObserver' in window)) return;

  new IntersectionObserver(
    ([entry]) => header.classList.toggle('is-stuck', !entry.isIntersecting),
    { rootMargin: '-1px 0px 0px 0px', threshold: 0 }
  ).observe(sentinel);
}

/* -------------------------------------------------------------------------- */
/* Countdown                                                                  */
/* -------------------------------------------------------------------------- */

function initCountdown() {
  const root = document.querySelector('[data-countdown]');
  if (!root || !config.launchDate) return;

  const target = new Date(config.launchDate).getTime();
  // Invalid or already-passed date: leave the section as plain copy.
  if (Number.isNaN(target) || target <= Date.now()) return;

  const fields = {
    days: root.querySelector('[data-unit="days"]'),
    hours: root.querySelector('[data-unit="hours"]'),
    minutes: root.querySelector('[data-unit="minutes"]'),
    seconds: root.querySelector('[data-unit="seconds"]'),
  };
  const srSummary = root.querySelector('[data-countdown-sr]');

  const pad = (value) => String(value).padStart(2, '0');

  function tick() {
    const remaining = target - Date.now();

    if (remaining <= 0) {
      root.hidden = true;
      clearInterval(timer);
      return;
    }

    const seconds = Math.floor(remaining / 1000);
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);

    fields.days.textContent = String(days);
    fields.hours.textContent = pad(hours);
    fields.minutes.textContent = pad(minutes);
    fields.seconds.textContent = pad(seconds % 60);

    // Screen readers get a single calm sentence, not a per-second update.
    if (srSummary) srSummary.textContent = `Launching in about ${days} days and ${hours} hours.`;
  }

  root.hidden = false;
  tick();
  const timer = setInterval(tick, 1000);
}

/* -------------------------------------------------------------------------- */
/* Subscription forms                                                         */
/* -------------------------------------------------------------------------- */

/** Practical email check: one @, a dot in the domain, no spaces. */
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

const MESSAGES = {
  invalid: 'That email address doesn’t look right. Check it and try again.',
  duplicate: 'This address is already on the list — nothing more to do.',
  network: 'We couldn’t reach the server. Check your connection and try again.',
  server: 'Something went wrong on our side. Try again in a moment.',
  not_configured: 'The waiting list isn’t accepting signups yet. Try again shortly.',
  rate_limited: 'That’s a lot of tries. Wait a minute, then try again.',
};

/** Show a status line under a form. */
function setStatus(form, message, kind) {
  const status = form.querySelector('[data-status]');
  if (!status) return;

  status.textContent = message;
  status.classList.toggle('is-error', kind === 'error');
  status.classList.toggle('is-success', kind === 'success');
  status.classList.toggle('is-visible', Boolean(message));
  form.classList.toggle('has-error', kind === 'error');
}

/** Swap the input row for a success card. */
function showSuccess(form) {
  const row = form.querySelector('.subscribe__row');
  const fineprint = form.querySelector('.subscribe__fineprint');
  if (!row) return;

  const done = document.createElement('div');
  done.className = 'subscribe__done';
  done.setAttribute('role', 'status');
  done.innerHTML = `
    <span class="subscribe__done-mark" aria-hidden="true">✓</span>
    <span>
      <span class="subscribe__done-title">Thank you!</span>
      <span class="subscribe__done-body">You have successfully joined our waiting list.</span>
    </span>`;

  row.replaceWith(done);
  if (fineprint) fineprint.remove();
  setStatus(form, '', null);
}

/** Remember locally which addresses this browser already sent. */
const SENT_KEY = 'dk_subscribed_emails';

function alreadySentFromThisBrowser(email) {
  try {
    const list = JSON.parse(sessionStorage.getItem(SENT_KEY) || '[]');
    return list.includes(email);
  } catch {
    return false;
  }
}

function rememberSent(email) {
  try {
    const list = JSON.parse(sessionStorage.getItem(SENT_KEY) || '[]');
    list.push(email);
    sessionStorage.setItem(SENT_KEY, JSON.stringify(list));
  } catch {
    /* Private mode or storage disabled — the database constraint still guards us. */
  }
}

function initForms() {
  const forms = document.querySelectorAll('[data-subscribe-form]');

  forms.forEach((form) => {
    const input = form.querySelector('input[type="email"]');
    const button = form.querySelector('.subscribe__submit');
    const label = button ? button.querySelector('[data-label]') : null;
    const honeypot = form.querySelector('.honeypot input');

    // Clear the error the moment someone starts fixing it.
    input.addEventListener('input', () => {
      if (form.classList.contains('has-error')) setStatus(form, '', null);
    });

    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      // A filled honeypot means a bot. Show success and drop the request.
      if (honeypot && honeypot.value.trim() !== '') {
        showSuccess(form);
        return;
      }

      const email = input.value.trim().toLowerCase();

      if (!EMAIL_PATTERN.test(email) || email.length > 254) {
        setStatus(form, MESSAGES.invalid, 'error');
        input.setAttribute('aria-invalid', 'true');
        input.focus();
        return;
      }
      input.removeAttribute('aria-invalid');

      if (alreadySentFromThisBrowser(email)) {
        setStatus(form, MESSAGES.duplicate, 'error');
        return;
      }

      // Lock the button so a double-tap can't create two rows.
      const original = label ? label.textContent : '';
      if (button) button.disabled = true;
      if (label) label.textContent = 'Adding you…';
      setStatus(form, '', null);

      try {
        await supabase.insertSubscription({
          email,
          ip_address: config.ipAddress || null,
          user_agent: navigator.userAgent.slice(0, 500),
          source: config.source || 'landing_page',
        });

        rememberSent(email);
        showSuccess(form);
      } catch (error) {
        const code = error instanceof SubscribeError ? error.code : 'server';
        setStatus(form, MESSAGES[code] || MESSAGES.server, 'error');
        if (code === 'server' || code === 'not_configured') console.error('DeenKonnect:', error);
        input.focus();
      } finally {
        if (button) button.disabled = false;
        if (label) label.textContent = original;
      }
    });
  });
}

/* -------------------------------------------------------------------------- */
/* Boot                                                                       */
/* -------------------------------------------------------------------------- */

initStickyHeader();
initReveals();
initCountdown();
initForms();
