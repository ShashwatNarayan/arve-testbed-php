# arve-testbed-php

> [!WARNING]
> **INTENTIONALLY VULNERABLE CODE. DO NOT DEPLOY, DO NOT RUN ON A REACHABLE HOST,
> DO NOT COPY INTO REAL PROJECTS.**
> This repository contains deliberately planted SQL injection, command injection,
> eval injection, path traversal, hardcoded credentials and vulnerable dependency
> pins. It exists only to evaluate the ARVE security scanner.

**All credentials in this repository and its Git history are synthetic.** They
were randomly generated and are correctly shaped, but they grant access to
nothing. No secret value appears in this README or in `EXPECTED_FINDINGS.md`,
which refer to file and line only.

## What it is

DocVault is a minimal plain PHP 8 + PDO SQLite document service (no framework,
Composer for dependencies) with 7 endpoints (upload, download, search, convert,
import-url, settings import, preview). `public/index.php` routes to the handlers
in `src/`. `expected-findings.json` is the answer key. Conventions shared with the
Python, JS/TS and Java testbeds are in [`TESTBED_SPEC.md`](TESTBED_SPEC.md).

## CodeQL is expected to be skipped

CodeQL has no PHP extractor, so `engine_expectations.codeql` is `"skipped"` and no
plant expects it. The correct ARVE outcome for this repository is a **`COMPLETED`**
scan with the CodeQL engine reported as skipped. A scan that ends `PARTIAL` or
`FAILED` because CodeQL could not run is an ARVE defect, not a testbed defect.

## Plants

Generated from `expected-findings.json` by `scripts/render_answer_key.py`. Do not edit.

<!-- BEGIN PLANT TABLE -->
| ID | Kind | Location | Expected engines | Rule IDs | CWE | Cross-engine group |
|---|---|---|---|---|---|---|
| SAST-01 | sast | `src/search.php:10` | semgrep | `semgrep: arve.php.sql-injection` | CWE-89 | - |
| SAST-02 | sast | `src/convert.php:24` | semgrep | `semgrep: arve.php.command-injection` | CWE-78 | - |
| SAST-03 | sast | `src/settings.php:9` | semgrep | `semgrep: arve.php.eval-injection` | CWE-95 | - |
| SAST-04 | sast | `src/docs.php:38` | semgrep | `semgrep: arve.php.path-traversal` | CWE-22 | - |
| SEC-01 | secret | `src/config.php:12` | gitleaks | `gitleaks: generic-api-key` | CWE-798 | - |
| SEC-02 | secret | `keys/signing.pem:2` | gitleaks | `gitleaks: private-key` | CWE-321 | - |
| SEC-03 | secret | `.github/workflows/ci.yml:13` | gitleaks | `gitleaks: generic-api-key` | CWE-798 | - |
| SEC-05 | secret | `src/notify.php` (history only) | gitleaks | `gitleaks: generic-api-key` | CWE-798 | - |
| SEC-06 | secret | `docs/runbook.md:25` | gitleaks | `gitleaks: slack-webhook-url` | CWE-798 | - |
| DEP-01 | dependency | `composer.lock` (guzzlehttp/guzzle==7.4.0) | osv | `osv: GHSA-25mq-v84q-4j7r, GHSA-94pj-82f3-465w, GHSA-cwmx-hcrq-mhc3, GHSA-cwxw-98qj-8qjx, GHSA-f283-ghqc-fg79, GHSA-f2wf-25xc-69c9, GHSA-f7vp-7xgx-4w4r, GHSA-g446-98w2-8p5w, GHSA-h95v-h523-3mw8, GHSA-q559-8m2m-g699, GHSA-v5mv-p594-2x33, GHSA-w248-ffj2-4v5q, GHSA-wm3w-8rrp-j577, GHSA-wpwq-4j6v-78m3` | - | - |
| DEP-02 | dependency | `composer.lock` (firebase/php-jwt==v5.5.1) | osv | `osv: GHSA-2x45-7fc3-mxwq, GHSA-8xf4-w7qw-pjjw` | - | - |
| DEP-03 | dependency | `composer.lock` (twig/twig==v3.4.2) | osv | `osv: GHSA-2g2g-8p8h-fgwm, GHSA-4j38-f5cw-54h7, GHSA-529h-vh3j-85hq, GHSA-52m2-vc4m-jj33, GHSA-5v5v-ww74-355v, GHSA-6377-hfv9-hqf6, GHSA-6j75-5wfj-gh66, GHSA-7fxw-r6jv-74c8, GHSA-7p85-w9px-jpjp, GHSA-8x9c-rmqh-456c, GHSA-h8vq-8gpg-mhcg, GHSA-jjxq-ff2g-95vh, GHSA-p42q-9prx-q5wq, GHSA-pr2w-4gpj-cpq4, GHSA-vcc8-phrv-43wj` | - | - |
| DEP-04 | dependency | `composer.lock` (erusev/parsedown==1.7.1) | osv | `osv: GHSA-62m3-fc7f-jpp8` | - | - |
| SAFE-01 | control | `src/repository.php:8` | none (negative control) | - | - | - |
| SAFE-02 | control | `src/convert.php:9` | none (negative control) | - | - | - |
| SAFE-03 | control | `src/docs.php:61` | none (negative control) | - | - | - |
<!-- END PLANT TABLE -->

`SEC-04` is deliberately unused: IDs are aligned with the Python and JS testbeds,
where `SEC-05` is the history-only secret and `SEC-06` the runbook webhook.

## How to scan

Requirements: Docker, Python 3, and a sibling checkout of ARVE at `../ARVE` for
the Semgrep rulepack. Override the path with `ARVE_RULES=<path to rules>`. PHP and
Composer are not needed to scan.

```bash
docker pull ghcr.io/gitleaks/gitleaks:v8.24.2
docker pull ghcr.io/google/osv-scanner:v1.9.2
docker pull semgrep/semgrep:1.90.0

python scripts/verify_plants.py --history          # three engines + git history
python scripts/verify_plants.py --engines semgrep  # one engine
```

Each (plant, engine) prints `PASS`/`FAIL`, followed by `UNEXPECTED` findings and
`COUNT` mismatches against `expected_counts`. CodeQL prints
`skipped by engine_expectations`. Raw reports land in `.scan/`. Sanitised
reference reports are committed in `baselines/` (there is no `codeql.sarif`).

## How to score an ARVE run

Scan this repository (**including Git history**, since SEC-05 exists only there)
with ARVE, then compare its findings with `expected-findings.json`:

- **Scan status**: `COMPLETED`, with CodeQL skipped (see above).
- **Recall**: each `positive` entry should be reported by each engine in
  `expected_engines`, at `file_path` within `line_start` … `line_start + line_span - 1`,
  under each rule in `rule_ids`. For DEP entries, match by package and version
  (`composer.lock` versions keep their `v` prefix where the lockfile has one).
- **False positives**: any finding on a `SAFE-*` line, or any finding not explained
  by an entry, is a false positive. `notes` in the key lists a known ARVE Semgrep
  false-positive pattern that SAFE-02 and SAFE-03 deliberately avoid.
- **Semgrep profile**: filter expectations by `semgrep_profile` and the rulepack
  manifest for the profile the run used.

## Maintenance

```bash
python scripts/render_answer_key.py           # markers -> JSON lines -> MD + this table
python scripts/seed_history.py --force        # rebuild the planted Git history (deletes .git)
python scripts/verify_plants.py --history --save-baselines
```

`composer.lock` is regenerated with
`composer update --no-install --no-security-blocking`; Composer 2.9+ otherwise
refuses to resolve the pinned vulnerable versions. The build check is `php -l`
over `public/` and `src/`.
