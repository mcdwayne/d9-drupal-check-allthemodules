#!/usr/bin/env bash
set -euo pipefail
IFS=$'\n\t'

#/ Usage:       ./package-excludes.sh <path>
#/ Description: Ensures that certain files are excluded from the drupal.org package.
#/ Options:
#/   --help: Display this help message
usage() { grep '^#/' "$0" | cut -c4- ; exit 0 ; }
expr "$*" : ".*--help" > /dev/null && usage

readonly LOG_FILE="/tmp/$(basename "$0").log"
info()    { echo "[INFO]    $*" | tee -a "$LOG_FILE" >&2 ; }
warning() { echo "[WARNING] $*" | tee -a "$LOG_FILE" >&2 ; }
error()   { echo "[ERROR]   $*" | tee -a "$LOG_FILE" >&2 ; }
fatal()   { echo "[FATAL]   $*" | tee -a "$LOG_FILE" >&2 ; exit 1 ; }

GIT_DIR=$@
TMP_DIR=/tmp/package-excludes

cleanup() {
  # Remove temporary files
  echo "cleaning up"
  rm -r "${TMP_DIR}"
}

if [[ "${BASH_SOURCE[0]}" = "$0" ]]; then
  trap cleanup EXIT

  mkdir -p ${TMP_DIR}
  cd ${TMP_DIR}

  RESULT="pass"
  git --git-dir="${GIT_DIR}/.git" archive --format=tar --output="encrypt_kms.tar" HEAD
  tar -vxxf encrypt_kms.tar
  grep 'export-ignore' "${GIT_DIR}/.gitattributes" | awk '{print $1}' | while read FILE; do
    MATCHES=$(find . -name "${FILE}")
    if [[ ! -z "${MATCHES}" ]]; then
      error "Excluded file found in package: ${FILE}"
      RESULT="fail"
    fi
  done

  if [ "${RESULT}" != "pass" ]; then
    fatal "Test failed"
  fi
fi
