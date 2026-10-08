#!/usr/bin/env bash
set -eo pipefail

# Reuse the pinned official initializer; keep password values in shell memory only.
source /usr/local/bin/docker-entrypoint.sh

file_env() {
    local var="$1" fileVar="${1}_FILE" val="${2:-}"
    if [[ "$var" == MYSQL_PASSWORD || "$var" == MYSQL_ROOT_PASSWORD ]]; then
        if [[ -n "${!var:-}" || -z "${!fileVar:-}" || ! -r "${!fileVar}" ]]; then
            mysql_error 'Database passwords must come from readable mounted files.'
        fi
        val="$(< "${!fileVar}")"
        if [[ ! "$val" =~ ^[a-f0-9]{64}$ ]]; then
            mysql_error 'Invalid development database password file.'
        fi
        unset "$var"
        printf -v "$var" '%s' "$val"
        # Retain the pointer for the privilege-drop process; never export the value.
        return
    fi
    if [[ -n "${!var:-}" && -n "${!fileVar:-}" ]]; then
        mysql_error 'Conflicting database configuration sources.'
    fi
    if [[ -n "${!var:-}" ]]; then
        val="${!var}"
    elif [[ -n "${!fileVar:-}" ]]; then
        val="$(< "${!fileVar}")"
    fi
    export "$var"="$val"
    unset "$fileVar"
}

# The upstream root branch re-executes its own file. Drop privileges here so
# both processes use this file adapter rather than the upstream exporting adapter.
if [[ "$(id -u)" == 0 ]]; then
    mysql_check_config "$@"
    docker_setup_env "$@"
    docker_create_db_directories "$@"
    exec gosu mysql bash /naturally-mysql.sh "$@"
fi

_main "$@"
