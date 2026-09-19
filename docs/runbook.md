# DocVault runbook

## Service

DocVault is a single PHP 8 application served from `public/index.php`. State lives
in one SQLite file (`storage/docvault.db`) and uploaded files in `storage/files/`.

## Deploy

1. `composer install --no-dev -q`
2. Point the web server's document root at `public/`.
3. Make `storage/` writable by the web server user.

## Conversion failures

`POST /docs/{id}/convert` shells out to `pandoc`. If conversions start returning
502, check that `pandoc` and `file` are on the web server user's `PATH`.

## Alerts

Conversion and import failures are posted to the `#docvault-alerts` Slack channel.
The incoming webhook for that channel:

<!-- TESTBED SEC-06 -->
https://hooks.slack.com/services/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
