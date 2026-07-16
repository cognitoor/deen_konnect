<?php
/**
 * DeenKonnect — Landing page
 * --------------------------
 * Single entry point. Renders the pre-launch page and hands the browser the
 * public configuration it needs (Supabase endpoint, launch date, client IP).
 */

declare(strict_types=1);

$config = require __DIR__ . '/config/config.php';

$site     = $config['site'];
$supabase = $config['supabase'];

// Public config handed to the JavaScript layer. Contains no secrets.
$publicConfig = [
    'supabaseUrl'   => $supabase['url'],
    'supabaseKey'   => $supabase['anon_key'],
    'table'         => $supabase['table'],
    'source'        => $config['request']['source'],
    'ipAddress'     => $config['request']['ip'],
    'launchDate'    => $site['launch_date'],
];

$canonical = $site['url'] . '/';
$year      = date('Y');

// Send a few defensive headers before any output.
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('X-Frame-Options: SAMEORIGIN');
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="theme-color" content="#0F6D4A">

<title><?= dk_e($site['name']) ?> — Connecting Muslims with trusted Islamic scholars</title>
<meta name="description" content="<?= dk_e($site['description']) ?>">
<meta name="robots" content="index, follow, max-image-preview:large">
<link rel="canonical" href="<?= dk_e($canonical) ?>">

<!-- Open Graph -->
<meta property="og:type" content="website">
<meta property="og:site_name" content="<?= dk_e($site['name']) ?>">
<meta property="og:title" content="<?= dk_e($site['name']) ?> — Connecting Muslims with trusted Islamic scholars">
<meta property="og:description" content="<?= dk_e($site['description']) ?>">
<meta property="og:url" content="<?= dk_e($canonical) ?>">
<meta property="og:locale" content="<?= dk_e($site['locale']) ?>">
<meta property="og:image" content="<?= dk_e($site['url']) ?>/assets/images/og-image.png">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:alt" content="DeenKonnect — knowledge, connected.">

<!-- Twitter -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= dk_e($site['name']) ?> — Connecting Muslims with trusted Islamic scholars">
<meta name="twitter:description" content="<?= dk_e($site['description']) ?>">
<meta name="twitter:image" content="<?= dk_e($site['url']) ?>/assets/images/og-image.png">

<!-- Icons -->
<link rel="icon" href="assets/images/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">

<!-- Fonts: preconnect first so the handshake overlaps with CSS download -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet"
      href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&family=Inter:wght@400;500;600&display=swap">

<link rel="stylesheet" href="assets/css/style.css">

<!-- Structured data -->
<script type="application/ld+json">
<?= json_encode([
    '@context'    => 'https://schema.org',
    '@type'       => 'Organization',
    'name'        => $site['name'],
    'url'         => $site['url'],
    'logo'        => $site['url'] . '/assets/images/logo.svg',
    'email'       => $site['email'],
    'description' => $site['description'],
    'sameAs'      => [
        'https://twitter.com/deenkonnect',
        'https://www.instagram.com/deenkonnect',
        'https://www.linkedin.com/company/deenkonnect',
    ],
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>

<!-- Public runtime config. No secrets: the anon key is protected by row level security. -->
<script id="dk-config" type="application/json">
<?= json_encode($publicConfig, JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>
</script>
</head>

<body>
<a class="skip-link" href="#main">Skip to content</a>

<!-- ============================ Header ============================ -->
<header class="site-header" id="top">
  <div class="shell header__inner">
    <a class="brand" href="#top" aria-label="DeenKonnect home">
      <svg class="brand__mark" viewBox="0 0 48 48" role="img" aria-hidden="true" focusable="false">
        <use href="assets/icons/sprite.svg#mark"></use>
      </svg>
      <span class="brand__name">DeenKonnect</span>
    </a>

    <nav class="header__nav" aria-label="Primary">
      <a href="#about">About</a>
      <a href="#features">What we're building</a>
      <a href="#launch">Launch</a>
      <a class="btn btn--ghost header__cta" href="#subscribe">Join the list</a>
    </nav>
  </div>
</header>

<main id="main">

  <!-- ============================ Hero ============================ -->
  <section class="hero" aria-labelledby="hero-title">
    <!-- Signature motif: a hairline eight-point khatim star, drawn in gold, sitting behind the type. -->
    <div class="hero__motif" aria-hidden="true">
      <svg viewBox="0 0 400 400" preserveAspectRatio="xMidYMid meet">
        <use href="assets/icons/sprite.svg#khatim"></use>
      </svg>
    </div>

    <div class="shell hero__inner">
      <p class="eyebrow reveal">Launching soon · In shā’ Allāh</p>

      <h1 class="hero__title reveal" id="hero-title">
        Connecting Muslims with trusted<br>
        <span class="hero__title-accent">Islamic scholars</span> and authentic knowledge.
      </h1>

      <p class="hero__lede reveal">
        DeenKonnect brings verified scholars, imams and teachers within reach — so your
        questions reach people qualified to answer them.
      </p>

      <div class="hero__actions reveal">
        <a class="btn btn--primary" href="#subscribe">Join the waiting list</a>
        <a class="btn btn--quiet" href="#about">See what we're building</a>
      </div>

      <!-- Compact hero form: the same handler as the section below. -->
      <form class="subscribe subscribe--inline reveal" novalidate
            data-subscribe-form data-form-id="hero">
        <div class="subscribe__row">
          <label class="visually-hidden" for="hero-email">Email address</label>
          <input class="subscribe__input"
                 id="hero-email"
                 name="email"
                 type="email"
                 inputmode="email"
                 autocomplete="email"
                 placeholder="you@example.com"
                 aria-describedby="hero-status"
                 required>
          <button class="btn btn--primary subscribe__submit" type="submit">
            <span data-label>Notify me</span>
          </button>
        </div>
        <p class="subscribe__status" id="hero-status" role="status" aria-live="polite" data-status></p>
      </form>
    </div>
  </section>

  <!-- ============================ About ============================ -->
  <section class="section about" id="about" aria-labelledby="about-title">
    <div class="shell about__inner">
      <p class="eyebrow reveal">About</p>
      <h2 class="section__title reveal" id="about-title">A platform built around the people who teach</h2>
      <p class="about__body reveal">
        DeenKonnect is being built to connect Muslims with qualified scholars, imams, teachers
        and authentic Islamic guidance. Every scholar on the platform is checked before they
        answer a single question — their teachers named, their credentials verified, their
        specialisation clear. Whether you need a considered answer on inheritance, a teacher
        for your children, or somewhere reliable to begin, the aim is the same: knowledge you
        can trust, from people you can name.
      </p>
    </div>
  </section>

  <!-- ============================ Features ============================ -->
  <section class="section features" id="features" aria-labelledby="features-title">
    <div class="shell">
      <p class="eyebrow reveal">What we're building</p>
      <h2 class="section__title reveal" id="features-title">Four things we're getting right first</h2>

      <ul class="features__grid" role="list">
        <li class="card reveal">
          <svg class="card__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <use href="assets/icons/sprite.svg#verified"></use>
          </svg>
          <h3 class="card__title">Verified scholars</h3>
          <p class="card__body">
            Credentials, ijāzah and teaching lineage are checked before a profile goes live.
            You always know who is answering.
          </p>
        </li>

        <li class="card reveal">
          <svg class="card__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <use href="assets/icons/sprite.svg#consult"></use>
          </svg>
          <h3 class="card__title">Easy consultation</h3>
          <p class="card__body">
            Ask a question, book a session, or write privately. Choose the scholar and the
            madhhab you follow.
          </p>
        </li>

        <li class="card reveal">
          <svg class="card__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <use href="assets/icons/sprite.svg#knowledge"></use>
          </svg>
          <h3 class="card__title">Islamic knowledge</h3>
          <p class="card__body">
            Courses, readings and answers kept in one library — sourced, referenced and
            searchable in English, Arabic and Urdu.
          </p>
        </li>

        <li class="card reveal">
          <svg class="card__icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
            <use href="assets/icons/sprite.svg#community"></use>
          </svg>
          <h3 class="card__title">Community support</h3>
          <p class="card__body">
            Find your local masjid, join study circles, and learn alongside students at the
            same stage as you.
          </p>
        </li>
      </ul>
    </div>
  </section>

  <!-- ============================ Coming soon ============================ -->
  <section class="section launch" id="launch" aria-labelledby="launch-title">
    <div class="shell launch__inner">
      <p class="eyebrow eyebrow--onDark reveal">Coming soon</p>
      <h2 class="section__title section__title--onDark reveal" id="launch-title">Launching soon</h2>
      <p class="launch__body reveal">
        We're finishing scholar verification and opening the doors in stages. The waiting list
        goes first.
      </p>

      <!-- Countdown. Hidden until JS confirms a valid future launch date. -->
      <div class="countdown reveal" data-countdown hidden>
        <div class="countdown__unit">
          <span class="countdown__value" data-unit="days" aria-hidden="true">—</span>
          <span class="countdown__label">Days</span>
        </div>
        <div class="countdown__unit">
          <span class="countdown__value" data-unit="hours" aria-hidden="true">—</span>
          <span class="countdown__label">Hours</span>
        </div>
        <div class="countdown__unit">
          <span class="countdown__value" data-unit="minutes" aria-hidden="true">—</span>
          <span class="countdown__label">Minutes</span>
        </div>
        <div class="countdown__unit">
          <span class="countdown__value" data-unit="seconds" aria-hidden="true">—</span>
          <span class="countdown__label">Seconds</span>
        </div>
        <!-- Screen readers get a calm, plain-language summary instead of a ticking timer. -->
        <p class="visually-hidden" data-countdown-sr aria-live="off"></p>
      </div>
    </div>
  </section>

  <!-- ============================ Subscribe ============================ -->
  <section class="section subscribe-section" id="subscribe" aria-labelledby="subscribe-title">
    <div class="shell subscribe-section__inner">
      <p class="eyebrow reveal">Stay updated</p>
      <h2 class="section__title reveal" id="subscribe-title">Be there on day one</h2>
      <p class="subscribe-section__body reveal">
        Subscribe to receive launch updates. One email when we open, and nothing else.
      </p>

      <form class="subscribe reveal" novalidate data-subscribe-form data-form-id="main">
        <div class="subscribe__row">
          <label class="visually-hidden" for="main-email">Email address</label>
          <input class="subscribe__input"
                 id="main-email"
                 name="email"
                 type="email"
                 inputmode="email"
                 autocomplete="email"
                 placeholder="Email address"
                 aria-describedby="main-status"
                 required>
          <button class="btn btn--primary subscribe__submit" type="submit">
            <span data-label>Notify me</span>
          </button>
        </div>

        <!-- Honeypot: real people never see or fill this. -->
        <div class="honeypot" aria-hidden="true">
          <label for="main-company">Company</label>
          <input id="main-company" name="company" type="text" tabindex="-1" autocomplete="off">
        </div>

        <p class="subscribe__status" id="main-status" role="status" aria-live="polite" data-status></p>
        <p class="subscribe__fineprint">We store your email address to tell you about the launch. Unsubscribe any time.</p>
      </form>
    </div>
  </section>

</main>

<!-- ============================ Footer ============================ -->
<footer class="site-footer">
  <div class="shell footer__inner">
    <div class="footer__brand">
      <a class="brand" href="#top" aria-label="DeenKonnect home">
        <svg class="brand__mark" viewBox="0 0 48 48" role="img" aria-hidden="true" focusable="false">
          <use href="assets/icons/sprite.svg#mark"></use>
        </svg>
        <span class="brand__name brand__name--onDark">DeenKonnect</span>
      </a>
      <p class="footer__tagline">Knowledge, connected.</p>
    </div>

    <nav class="footer__links" aria-label="Footer">
      <a href="/privacy.php">Privacy policy</a>
      <a href="/terms.php">Terms</a>
      <a href="mailto:<?= dk_e($site['email']) ?>">Contact</a>
    </nav>

    <ul class="footer__social" role="list" aria-label="DeenKonnect on social media">
      <li>
        <a href="https://twitter.com/deenkonnect" rel="me noopener" aria-label="DeenKonnect on X">
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><use href="assets/icons/sprite.svg#x"></use></svg>
        </a>
      </li>
      <li>
        <a href="https://www.instagram.com/deenkonnect" rel="me noopener" aria-label="DeenKonnect on Instagram">
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><use href="assets/icons/sprite.svg#instagram"></use></svg>
        </a>
      </li>
      <li>
        <a href="https://www.linkedin.com/company/deenkonnect" rel="me noopener" aria-label="DeenKonnect on LinkedIn">
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><use href="assets/icons/sprite.svg#linkedin"></use></svg>
        </a>
      </li>
      <li>
        <a href="https://www.youtube.com/@deenkonnect" rel="me noopener" aria-label="DeenKonnect on YouTube">
          <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><use href="assets/icons/sprite.svg#youtube"></use></svg>
        </a>
      </li>
    </ul>

    <p class="footer__legal">© <?= dk_e((string) $year) ?> DeenKonnect. All rights reserved.</p>
  </div>
</footer>

<script type="module" src="assets/js/app.js"></script>
</body>
</html>
