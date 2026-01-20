#!/bin/bash
set -e

# Ensure database directory permissions
if [ -d "/app/database" ]; then
    # We can't use chown if we are not root or if the volume is mounted with specific permissions.
    # But usually in Docker we run as root unless specified otherwise.
    # The PHP image used www-data, but alpine runs as root by default.
    :
fi

# Create database file if it doesn't exist, to ensure permissions are right when created
if [ ! -f "/app/database/database.sqlite" ]; then
    touch /app/database/database.sqlite
fi

exec "$@"
