#!/bin/sh
# print the directory and file.
cd /var/www/drupal
echo $(pwd)

for file in `find modules/idcp -name "*.info.yml"`; do
  echo $(dirname $file) -- $(basename $(dirname $file));
  ./vendor/bin/drush potx single --include=modules/contrib/potx --folder="$(dirname $file)" --api=8
  if [ ! -d $(dirname $file)/translations ]; then
    mkdir $(dirname $file)/translations;
    echo "mkdir $(dirname $file)/translations";
  fi
  mv ./general.pot "$(dirname $file)/translations/$(basename $(dirname $file)).pot"
done
