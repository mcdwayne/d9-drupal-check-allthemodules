#!/usr/bin/env bash

source /opt/app-root/scripts/util/hostname.sh


if [ "$BOOTSTRAP" != "false" ]; then
    source /opt/app-root/scripts/util/bootstrap.sh
    ../vendor/bin/drupal module:install config
    ../vendor/bin/drupal theme:install seven --set-default
    mkdir -p ./libraries/ace/src-min-noconflict/
    curl -o ./libraries/ace/src-min-noconflict/ace.js -D - -L -s https://github.com/ajaxorg/ace-builds/blob/master/src-min-noconflict/ace.js
fi

if [ "$DEFAULT_CONFIG" != "false" ]; then
    ../vendor/bin/drupal config:import:single --file /opt/app-root/test/dev/elastic_search.server.yml
fi

# Keep the container running with the hold script from the drupal_module_tester image
source /opt/app-root/scripts/util/hold.sh