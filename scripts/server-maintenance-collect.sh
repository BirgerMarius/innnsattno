#!/usr/bin/env bash
# Installed and run as root; Laravel and the web user never execute this file.
set -euo pipefail
status_dir="${SERVER_MAINTENANCE_STATUS_DIR:-/var/lib/innsatt-server-maintenance}"
status_file="${SERVER_MAINTENANCE_STATUS_FILE:-${status_dir}/status.json}"
error_file="${SERVER_MAINTENANCE_ERROR_FILE:-${status_dir}/status.error.json}"
lock_file="${SERVER_MAINTENANCE_LOCK_FILE:-/run/lock/innsatt-server-maintenance.lock}"
run_group="${SERVER_MAINTENANCE_READ_GROUP:-forge}"
apt_timeout="${SERVER_MAINTENANCE_APT_TIMEOUT:-180}"
umask 027
install -d -m 0750 -o root -g "$run_group" "$status_dir"
exec 9>"$lock_file"; flock -n 9 || exit 0
checked_at="$(date --iso-8601=seconds)"; tmp="$(mktemp "${status_dir}/.status.XXXXXX")"
trap 'rm -f "$tmp" "${simulated:-}"' EXIT
publish_error() { python3 - "$tmp" "$checked_at" "$1" <<'PY'
import json, sys
json.dump({'schema_version':1,'status':'error','checked_at':sys.argv[2],'error':sys.argv[3]}, open(sys.argv[1],'w'), separators=(',',':'))
PY
chown root:"$run_group" "$tmp"; chmod 0640 "$tmp"; mv -f "$tmp" "$error_file"; }
update_log="$(mktemp)"
trap 'rm -f "$tmp" "${simulated:-}" "$update_log"' EXIT
if ! timeout "$apt_timeout" env LC_ALL=C apt-get update -o Acquire::Retries=0 >"$update_log" 2>&1 \
    || grep -Eq '^(W: Some index files failed|E:)' "$update_log"; then
    publish_error 'apt-get update feilet, hadde delvise kildefeil eller gikk ut på tid.'; exit 1
fi
simulated="$(mktemp)"
if ! timeout "$apt_timeout" env LC_ALL=C apt-get -s upgrade >"$simulated" 2>/dev/null; then publish_error 'APT-simulering feilet eller gikk ut på tid.'; exit 1; fi
mapfile -t names < <({ awk '/^Inst / { print $2 }' "$simulated"; apt list --upgradable 2>/dev/null | awk -F/ 'NR > 1 { print $1 }'; } | sort -u)
holds="$(apt-mark showhold 2>/dev/null || true)"; reboot_required=false; [[ -e /var/run/reboot-required ]] && reboot_required=true
python3 - "$tmp" "$checked_at" "$reboot_required" "$holds" "${names[@]}" <<'PY'
import json, subprocess, sys
path, checked, reboot, holds, *names = sys.argv[1:]; held=set(holds.splitlines()); packages=[]
for name in names:
    out=subprocess.run(['apt-cache','policy',name],text=True,stdout=subprocess.PIPE,stderr=subprocess.DEVNULL,timeout=15).stdout
    installed=candidate=None
    for line in out.splitlines():
        line=line.strip()
        if line.startswith('Installed: '): installed=line[11:]
        if line.startswith('Candidate: '): candidate=line[11:]
    if installed and candidate and installed not in ('(none)',candidate) and candidate != '(none)': packages.append({'name':name,'installed_version':installed,'candidate_version':candidate,'held':name in held})
packages.sort(key=lambda p:p['name'])
json.dump({'schema_version':1,'status':'ok','checked_at':checked,'reboot_required':reboot=='true','packages':packages},open(path,'w'),separators=(',',':'))
PY
chown root:"$run_group" "$tmp"; chmod 0640 "$tmp"; mv -f "$tmp" "$status_file"; rm -f "$error_file"
