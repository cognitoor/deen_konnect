<?php
/**
 * DeenKonnect — Application configuration
 * ---------------------------------------
 * Loads settings from the environment. Nothing secret is ever hardcoded here.
 *
 * Values are read in this order:
 *   1. Real environment variables (set by the web server / container / host panel)
 *   2. A local .env file in the project root (development convenience only)
 *
 * The .env file must never be committed. See README.md.
 */

declare(strict_types=1);

/**
 * Read a `KEY=value` style .env file into the process environment.
 * Existing environment variables always win, so production hosts stay authoritative.
 */
function dk_load_env_file(string $path): void
{
    if (!is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and malformed lines.
        if ($line === '' || str_starts_with($line, '#') || !str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Strip optional surrounding quotes.
        if (strlen($value) > 1) {
            $first = $value[0];
            $last  = $value[strlen($value) - 1];
            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                $value = substr($value, 1, -1);
            }
        }

        if (getenv($key) === false) {
            putenv("$key=$value");
        }
    }
}

/**
 * Fetch a configuration value with an optional fallback.
 */
function dk_env(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return ($value === false || $value === '') ? $default : $value;
}

/**
 * Escape a value for safe output inside HTML.
 */
function dk_e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Best-effort client IP, honouring common proxy headers.
 * Returns null when nothing trustworthy is available.
 */
function dk_client_ip(): ?string
{
    $candidates = [];

    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        $candidates[] = $_SERVER['HTTP_CF_CONNECTING_IP'];
    }
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // May be a comma separated chain; the left-most entry is the original client.
        $candidates[] = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    }
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $candidates[] = $_SERVER['REMOTE_ADDR'];
    }

    foreach ($candidates as $candidate) {
        $candidate = trim($candidate);
        if (filter_var($candidate, FILTER_VALIDATE_IP)) {
            return $candidate;
        }
    }

    return null;
}

dk_load_env_file(dirname(__DIR__) . '/.env');

/**
 * Application config consumed by index.php.
 */
return [
    'site' => [
        'name'        => 'DeenKonnect',
        'tagline'     => 'Knowledge, connected.',
        'description' => 'DeenKonnect connects Muslims with verified scholars, imams and teachers for authentic Islamic guidance.',
        'url'         => rtrim((string) dk_env('SITE_URL', 'https://deenkonnect.com'), '/'),
        'locale'      => 'en_US',
        'launch_date' => dk_env('LAUNCH_DATE', '2026-11-01T09:00:00Z'), // ISO 8601, UTC
        'email'       => dk_env('CONTACT_EMAIL', 'salam@deenkonnect.com'),
    ],

    // The anon key is a public, row-level-security-protected key by design —
    // but it is still read from the environment so keys can rotate per deployment.
    'supabase' => [
        'url'      => dk_env('SUPABASE_URL', 'https://vydqvpmnxzueizuqpmzq.supabase.co'),
        'anon_key' => dk_env('SUPABASE_ANON_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6InZ5ZHF2cG1ueHp1ZWl6dXFwbXpxIiwicm9sZSI6ImFub24iLCJpYXQiOjE3ODQxODg4NDgsImV4cCI6MjA5OTc2NDg0OH0.0LJ4w8IJJu2KPrrkKQ7jyPRES6TqWro-3qyakNShCvI'),
        'table'    => dk_env('SUPABASE_TABLE', 'subscriptions'),
    ],

    'request' => [
        'ip'     => dk_client_ip(),
        'source' => 'landing_page',
    ],
];
