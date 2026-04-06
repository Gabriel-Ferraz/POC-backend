#!/bin/sh
set -e

SOCKET="unix:///var/run/supervisor.sock"
WORKERS="octane nginx"
FAILED=0

for worker in $WORKERS; do
    STATUS=$(supervisorctl -s "$SOCKET" status "$worker" 2>/dev/null | awk '{print $2}')

    if [ "$STATUS" != "RUNNING" ]; then
        echo "FAIL: $worker is $STATUS"
        FAILED=1
    fi
done

if [ "$FAILED" -ne 0 ]; then
    exit 1
fi

exit 0
