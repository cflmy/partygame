#!/usr/bin/env python3
"""Upload public/ contents to virtual host via FTP.

Usage:
  set FTP_HOST=...
  set FTP_USER=...
  set FTP_PASS=...
  python scripts/deploy_ftp.py
"""

from __future__ import annotations

import os
from ftplib import FTP, error_perm
from pathlib import Path

LOCAL_ROOT = Path(__file__).resolve().parent.parent / "public"
SKIP_NAMES = {".git", ".gitignore", "Thumbs.db", ".DS_Store"}


def env(name: str) -> str:
    value = os.environ.get(name, "").strip()
    if not value:
        raise SystemExit(f"missing environment variable: {name}")
    return value


def upload_tree(ftp: FTP, local_dir: Path, remote_dir: str = "") -> None:
    for entry in sorted(local_dir.iterdir()):
        if entry.name in SKIP_NAMES:
            continue

        remote_path = f"{remote_dir}/{entry.name}" if remote_dir else entry.name

        if entry.is_dir():
            try:
                ftp.mkd(remote_path)
            except error_perm:
                pass
            upload_tree(ftp, entry, remote_path)
            continue

        with entry.open("rb") as handle:
            ftp.storbinary(f"STOR {remote_path}", handle)
        print(f"uploaded: {remote_path}")


def remove_default_index(ftp: FTP) -> None:
    try:
        ftp.delete("index.html")
        print("removed: index.html")
    except error_perm as exc:
        print(f"skip remove index.html: {exc}")


def main() -> None:
    host = env("FTP_HOST")
    user = env("FTP_USER")
    password = env("FTP_PASS")
    port = int(os.environ.get("FTP_PORT", "21"))

    if not LOCAL_ROOT.is_dir():
        raise SystemExit(f"missing deploy source: {LOCAL_ROOT}")

    with FTP() as ftp:
        ftp.connect(host, port, timeout=60)
        ftp.login(user, password)
        ftp.set_pasv(True)
        print(f"connected: {host}")
        remove_default_index(ftp)
        upload_tree(ftp, LOCAL_ROOT)
        print("deploy complete")


if __name__ == "__main__":
    main()
