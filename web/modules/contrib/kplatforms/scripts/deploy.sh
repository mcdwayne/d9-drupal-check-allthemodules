#!/bin/bash
#
# Deploy currently built platforms based on the deploy.yml
# configuration, or another file if passed.
#
# Usage:
#   ./script/deploy.sh [deploy.yml]
#
# Note:
#   * platforms will not be built if they do not exist
#   * deployment via rsync/ssh is assumed
#   * no pre-deployment or post-deployment checks are performed
#
# An alternative timestamp for the deployments may be passed
# to the script through the DEPLOY_DATE environments variable.
# Eg.  DEPLOY_DATE=2047.01.01 ./script/deploy.sh
#
BASE_DIR=`pwd`
if [ ! -f "$BASE_DIR"/libraries/bash-yaml/yaml.sh ] ; then
    echo "Unable to load libraries/bash-yaml/yaml.sh"
    exit -1
fi

if [ -z "$1" ] ; then
    CONFIG_FILE="deploy.yml"
else
    CONFIG_FILE="$1"
fi
CONFIG_FILE="$BASE_DIR"/"$CONFIG_FILE"

if [ ! -f "$CONFIG_FILE" ] ; then
    echo "Configuration file $CONFIG_FILE not found"
    exit -2
fi

if [ -z "$DEPLOY_DATE" ] ; then
    DEPLOY_DATE=`date +'%Y.%m.%d'`
fi

if [ -z "$DEST" ] ; then
    DEST=build
fi

if [ -z "$BUILD_NUMBER" ] ; then
    BUILD_NUMBER=
fi

LIB_YAML="$BASE_DIR"/libraries/bash-yaml/yaml.sh
source "$LIB_YAML"

# For debug
parse_yaml "$CONFIG_FILE"

create_variables "$CONFIG_FILE"

function deploy_host() {
    local host=$1
    #echo $host
    local hn_key=`printf '%s__hostname' $host`
    local hostname="${!hn_key}"
    #echo $hostname
    local u_key=`printf '%s__user' $host`
    local user="${!u_key}"
    local bp_key=`printf '%s__basepath' $host`
    local basepath="${!bp_key}"
    #echo $basepath
    local p_key=`printf '%s__platforms_' $host`
    #echo $p_key
    #declare -p $p_key
    local -n platforms="$p_key"
    local aegir_provision_key=`printf %s__aegir__provision "$host"`
    local aegir_provision="${!aegir_provision_key}"
    for i in "${platforms[@]}" ; do
        local p_basename_var=`printf "%s__basename" $i | sed -e 's/-/_/g'`
        local basename=${!p_basename_var}
        if [ -z "$basename" ] ; then
            basename="$i"
        fi
        echo -ne "\tPlatform: $i... "
        if [ ! -d "${DEST}/${i}-${BUILD_NUMBER}" ] ; then
            echo "fail! Directory ${DEST}/${i}-${BUILD_NUMBER} does not exist."
            continue
        fi
        CURRENT_DATE="$DEPLOY_DATE" PLATFORM="$i" DEPLOY_USER="$user" \
        DEPLOY_HOST="$hostname" DEPLOY_PATH="$basepath" DEST="$DEST" \
        BUILD_NUMBER="$BUILD_NUMBER" DEPLOY_BASENAME="$basename" make \
        deploy-platform && echo "OK" || echo "FAILED: Returned $?"
        if [ "$aegir_provision" ] ; then
            path=$(get_deployed_platform_path "$basepath" "$basename" "$DEPLOY_DATE")
            sanitized_platform_name=$(sanitize_platform_name "${basename}_${DEPLOY_DATE}")
            echo "Running aegir provision on $hostname: ..."
            ssh "$user"@"$hostname" drush --root="$path" --context_type='platform' provision-save @platform_"$sanitized_platform_name"
            ssh "$user"@"$hostname" drush @hostmaster hosting-import @platform_"$sanitized_platform_name"
        fi
    done
}

function get_deployed_platform_path() {
    local path="$1"
    local basename="$2"
    local cur_date="$3"
    if [ -z "$cur_date" ] ; then
        cur_date=$(date +'%Y.%m.%d')
    fi
    echo "${path}/${basename}_${cur_date}"
}

function sanitize_platform_name() {
    local name="$1"
    # escape the name for sed first
    name=$(echo "$name" | sed 's/[-_.]//g;')
    echo "$name"
}

# Iterate over hosts
for i in "${hosts_[@]}" ; do
    echo "Host: $i"
    deploy_host "$i"
done
