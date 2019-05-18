#!/usr/bin/env bash
#
# Created by vcernomschi on 10/06/2015
#

source $(dirname $0)/_head.sh

########################
### Install NPM deps ###
########################
__CMD="npm install"

subpath_run_cmd "${__SRC_PATH}" "$__CMD" "$__CMD" "${1}"

if [ -z "${1}" ]; then
  __IS_CONCURRENT_SCRIPT=${__NONE};
else
  __IS_CONCURRENT_SCRIPT=${1}
fi

#####################################
### Add logging for imported vars ###
#####################################
if [ "$TRAVIS" == "true" ] && [ -e "${__VARS_FILE_PATH}" ]; then
  head -n 20 "${__VARS_FILE_PATH}"
fi

if [ "$__IS_CONCURRENT_SCRIPT" == "$__NONE" ] || [ "$__IS_CONCURRENT_SCRIPT" == "$__BACKEND" ]; then

  ##########################
  ### todo: install deps ###
  ##########################
  echo "Need to add install dependencies here"
fi
