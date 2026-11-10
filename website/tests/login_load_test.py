#!/usr/bin/env python3
"""Local/staging ONLY login concurrency evidence for the final assessment.

Create a separate JSON file outside the repo with >=5 disposable accounts:
[{"email":"test1@example.test","password":"...!"}, ...]
Then run:
  TEST_LOGIN_ACCOUNTS_FILE=/absolute/path/accounts.json python3 website/tests/login_load_test.py

Runs against localhost ONLY by design. Use a private staging server tunnel
bound to localhost if you have permission. Never load-test HelioHost live.
Does not log credentials or access tokens.
"""
from __future__ import annotations
import concurrent.futures
import json
import os
import statistics
import sys
import time
from collections import Counter
from pathlib import Path
from urllib.error import HTTPError, URLError
from urllib.parse import urlparse
from urllib.request import Request, urlopen

BASE = os.environ.get("TEST_API_URL", "http://127.0.0.1:8080/api/v1").rstrip("/")
parsed = urlparse(BASE)
if parsed.hostname not in ("127.0.0.1", "localhost", "::1"):
    sys.exit("ERROR: load test only allows localhost. Never run on live shared hosting.")
if parsed.scheme not in ("http", "https"):
    sys.exit("ERROR: valid local HTTP or HTTPS TEST_API_URL is required.")
file_path = os.environ.get("TEST_LOGIN_ACCOUNTS_FILE", "")
if not file_path:
    sys.exit("Set TEST_LOGIN_ACCOUNTS_FILE to your private JSON file of test accounts.")
accounts = json.loads(Path(file_path).read_text(encoding="utf-8"))
if not isinstance(accounts, list) or len(accounts) < 5:
    sys.exit("Supply at least 5 distinct disposable staging accounts.")
if len({str(a.get("email", "")).lower() for a in accounts}) != len(accounts):
    sys.exit("Accounts must have different email addresses.")
if any(not isinstance(a, dict) or not a.get("email") or not a.get("password") for a in accounts):
    sys.exit("Each test account requires email and password.")

def request_json(url: str, payload: dict, token: str | None = None) -> tuple[int, dict]:
    headers = {"Accept": "application/json", "Content-Type": "application/json"}
    if token:
        headers["Authorization"] = "Bearer " + token
    req = Request(url, data=json.dumps(payload).encode(), headers=headers, method="POST")
    try:
        with urlopen(req, timeout=20) as reply:
            return reply.status, json.loads(reply.read(4096) or b"{}")
    except HTTPError as ex:
        return ex.code, {}

def login_test(account: dict) -> tuple[int, float, str]:
    start = time.perf_counter()
    try:
        status, body = request_json(BASE + "/auth/login", {
            "email": account["email"], "password": account["password"],
            "device_name": "local-concurrency-evidence",
        })
        elapsed = (time.perf_counter() - start) * 1000
        token = body.get("data", {}).get("token") if status == 200 else None
        if token:
            try:
                request_json(BASE + "/auth/logout", {}, token=token)
            except Exception:
                pass
        return status, elapsed, "ok" if status == 200 else "http_error"
    except (TimeoutError, URLError) as exc:
        return 0, (time.perf_counter() - start) * 1000, "timeout_or_network"
    except Exception:
        return 0, (time.perf_counter() - start) * 1000, "invalid_response"

def percentile(values: list[float], frac: float) -> float:
    if not values: return 0.
    ordered = sorted(values)
    return ordered[min(len(ordered) - 1, max(0, int((len(ordered) - 1) * frac)))]

print("concurrency,requests,successes,http_failures,timeouts_or_network,p50_ms,p95_ms")
for concurrency in (1, 5, 10, 20):
    # Five login attempts per worker, from distinct test identities.
    batch = [accounts[i % len(accounts)] for i in range(concurrency * 5)]
    with concurrent.futures.ThreadPoolExecutor(max_workers=concurrency) as pool:
        results = list(pool.map(login_test, batch))
    successes = sum(status == 200 for status, _, _ in results)
    errors = sum(status not in (0, 200) for status, _, _ in results)
    network_failures = sum(status == 0 for status, _, _ in results)
    times = [elapsed for status, elapsed, kind in results if status == 200]
    print(f"{concurrency},{len(results)},{successes},{errors},{network_failures},"
          f"{percentile(times, 0.5):.1f},{percentile(times, 0.95):.1f}")
