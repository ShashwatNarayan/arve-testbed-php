#!/usr/bin/env python3
"""Rebuild the planted Git history for this testbed (deletes and recreates .git).

The commit sequence is part of the fixture: the history-only secret (SEC-05)
exists only in the commits between the one that adds its file and the one that
deletes it. A working-tree scan must not see it; `gitleaks git` must.

Language-neutral design (ported from arve-ledger-testbed/seed_history.py):

* COMMITS below is the only repo-specific part: an ordered list of
  (message, paths to stage from the working tree, paths to delete). Every file is
  added once, at its final content, so a HEAD secret is reported in history at
  the same file and line as at HEAD.
* HISTORY_ONLY holds the files that do not exist at HEAD. Their secret value is
  *derived* from a fixed seed, never written literally here. A literal would
  sit at HEAD (this script is committed), and a working-tree scan would then
  report it, which destroys the plant. This is not obfuscation: in the commits
  where it exists the value is ordinary plain text.
* The last commit stages everything else (`git add -A`), so new files never
  need listing.
* Author, dates and core.autocrlf=input are pinned, so the commits before the
  final one hash identically on every rebuild while their files are unchanged.

Usage:
    python scripts/seed_history.py --force
    python scripts/seed_history.py --print-sec05-line   # line number only, no value

INTENTIONALLY VULNERABLE TESTBED - see EXPECTED_FINDINGS.md.
"""

import argparse
import hashlib
import os
import shutil
import stat
import string
import subprocess
from datetime import datetime, timedelta, timezone
from pathlib import Path

REPO = Path(__file__).resolve().parent.parent
AUTHOR_NAME = os.environ.get("SEED_AUTHOR_NAME", "Shashwat Narayan")
AUTHOR_EMAIL = os.environ.get("SEED_AUTHOR_EMAIL", "shashwatn2802@gmail.com")
START = datetime(2026, 9, 14, 9, 30, tzinfo=timezone.utc)

SEC05_SEED = "arve-testbed-php/SEC-05/smtp-relay-token"


def derive_secret(seed, length=32, alphabet=string.ascii_letters + string.digits):
    """Deterministically expand a seed into a high-entropy string."""
    out, counter = [], 0
    while len(out) < length:
        block = hashlib.sha256(f"{seed}:{counter}".encode()).digest()
        out.extend(alphabet[b % len(alphabet)] for b in block)
        counter += 1
    return "".join(out[:length])


NOTIFY_PHP = '''<?php

declare(strict_types=1);

const SMTP_HOST = 'smtp.docvault.internal';
const SMTP_USER = 'docvault-notify';
const SMTP_RELAY_TOKEN = '{token}';

function send_conversion_failed(int $docId, string $recipient): bool
{{
    ini_set('SMTP', SMTP_HOST);
    $headers = 'From: docvault@docvault.internal' . PHP_EOL
        . 'X-Relay-Auth: ' . SMTP_USER . ':' . SMTP_RELAY_TOKEN;
    $subject = 'DocVault: conversion of document ' . $docId . ' failed';
    $body = 'The converter exited with an error. See the server log for details.';
    return mail($recipient, $subject, $body, $headers);
}}
'''

HISTORY_ONLY = {"src/notify.php": lambda: NOTIFY_PHP.format(token=derive_secret(SEC05_SEED))}

# (message, add from working tree / HISTORY_ONLY, delete)
COMMITS = [
    ("Initial DocVault project layout",
     [".gitignore", "composer.json", "composer.lock", "public/index.php", "src/config.php",
      "src/http.php", "src/db.php"], []),
    ("Add document upload, download and preview",
     ["src/docs.php", "src/repository.php", "src/token.php", "templates/preview.html.twig"], []),
    ("Add title search", ["src/search.php"], []),
    ("Add document conversion with email notification on failure",
     ["src/convert.php", "src/notify.php"], []),
    ("Add URL import and settings bundle import", ["src/fetch.php", "src/settings.php"], []),
    ("Replace email notifications with Slack alerts", ["docs/runbook.md"], ["src/notify.php"]),
    ("Add RSA key for signing exports", ["keys/signing.pem"], []),
    ("Add CI syntax check", [".github/workflows/ci.yml"], []),
    ("Add testbed tooling, answer key and docs", None, []),  # None = git add -A
]


def git(*args):
    env = dict(os.environ, GIT_AUTHOR_NAME=AUTHOR_NAME, GIT_AUTHOR_EMAIL=AUTHOR_EMAIL,
               GIT_COMMITTER_NAME=AUTHOR_NAME, GIT_COMMITTER_EMAIL=AUTHOR_EMAIL)
    return subprocess.run(["git", *args], cwd=REPO, env=env, check=True,
                          capture_output=True, text=True).stdout


def commit(index, message):
    when = (START + timedelta(days=index, hours=index % 3)).isoformat()
    os.environ["GIT_AUTHOR_DATE"] = os.environ["GIT_COMMITTER_DATE"] = when
    git("commit", "-q", "-m", message)


def sec05_line():
    text = HISTORY_ONLY["src/notify.php"]()
    return next(i for i, line in enumerate(text.splitlines(), 1) if "SMTP_RELAY_TOKEN =" in line)


def _rm_readonly(func, path, _exc):
    os.chmod(path, stat.S_IWRITE)
    func(path)


def main():
    parser = argparse.ArgumentParser(description=__doc__.splitlines()[0])
    parser.add_argument("--force", action="store_true", help="required: deletes and recreates .git")
    parser.add_argument("--print-sec05-line", action="store_true")
    args = parser.parse_args()
    if args.print_sec05_line:
        print(sec05_line())
        return
    if not args.force:
        raise SystemExit("This deletes and recreates .git. Re-run with --force.")

    remote = None
    if (REPO / ".git").exists():
        try:
            remote = git("remote", "get-url", "origin").strip()
        except subprocess.CalledProcessError:
            pass
        shutil.rmtree(REPO / ".git", onerror=_rm_readonly)
    git("init", "-q", "-b", "main")
    git("config", "core.autocrlf", "input")

    for index, (message, add, delete) in enumerate(COMMITS):
        if add is None:
            git("add", "-A")
        else:
            for rel in add:
                if rel in HISTORY_ONLY:
                    path = REPO / rel
                    path.parent.mkdir(parents=True, exist_ok=True)
                    path.write_bytes(HISTORY_ONLY[rel]().encode())
                git("add", "--", rel)
        for rel in delete:
            git("rm", "-q", "--", rel)
        commit(index, message)

    for rel in HISTORY_ONLY:
        assert not (REPO / rel).exists(), f"{rel} must not exist at HEAD"
    if remote:
        git("remote", "add", "origin", remote)
    print(f"{len(COMMITS)} commits; HEAD {git('rev-parse', '--short', 'HEAD').strip()}")


if __name__ == "__main__":
    main()
