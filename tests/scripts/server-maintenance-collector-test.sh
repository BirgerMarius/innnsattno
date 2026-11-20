#!/usr/bin/env bash
set -euo pipefail
root="$(cd "$(dirname "$0")/../.." && pwd)"
work="$(mktemp -d)"
trap 'rm -rf "$work"' EXIT
mkdir -p "$work/bin" "$work/status"
printf '%s\n' '#!/usr/bin/env bash' 'if [[ "$1" == update ]]; then case "${APT_MODE:-ok}" in partial) echo "W: Some index files failed to download.";; fail) exit 1;; timeout) sleep 2;; esac; exit 0; fi' 'echo "Inst nginx [1.0] (1.1 repo)"' >"$work/bin/apt-get"
printf '%s\n' '#!/usr/bin/env bash' 'echo "Listing..."' 'echo "nginx/noble 1.1 amd64 [upgradable from: 1.0]"' >"$work/bin/apt"
printf '%s\n' '#!/usr/bin/env bash' 'printf "Installed: 1.0\\nCandidate: 1.1\\n"' >"$work/bin/apt-cache"
printf '%s\n' '#!/usr/bin/env bash' 'echo nginx' >"$work/bin/apt-mark"
chmod +x "$work/bin"/*
run() { PATH="$work/bin:$PATH" SERVER_MAINTENANCE_STATUS_DIR="$work/status" SERVER_MAINTENANCE_READ_GROUP=root SERVER_MAINTENANCE_APT_TIMEOUT=1 bash "$root/scripts/server-maintenance-collect.sh"; }
run
python3 - "$work/status/status.json" <<'PY'
import json,sys
p=json.load(open(sys.argv[1])); assert p['packages'][0]['held']; assert p['packages'][0]['candidate_version']=='1.1'
PY
good="$(sha256sum "$work/status/status.json")"
if APT_MODE=partial run; then exit 1; fi
test -f "$work/status/status.error.json"; test "$good" = "$(sha256sum "$work/status/status.json")"
if APT_MODE=timeout run; then exit 1; fi
test "$good" = "$(sha256sum "$work/status/status.json")"
flock "$work/lock" sleep 2 & lock_pid=$!; sleep 0.1
SERVER_MAINTENANCE_LOCK_FILE="$work/lock" PATH="$work/bin:$PATH" SERVER_MAINTENANCE_STATUS_DIR="$work/status" SERVER_MAINTENANCE_READ_GROUP=root bash "$root/scripts/server-maintenance-collect.sh"
wait "$lock_pid"
