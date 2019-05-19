#!/bin/bash
#
# This script is run when the Drupal docker container is ready. It prepares
# an environment for development or testing, which contains a full Drupal
# 8 installation with a running website and our custom module.
#
set -e

echo "Will try to connect to MySQL container until it is up. This can take about 15 seconds."
OUTPUT="ERROR"
while [[ "$OUTPUT" == *"ERROR"* ]]
do
  OUTPUT=$(echo 'show databases'|{ mysql -h mysql -u root --password=drupal 2>&1 || true; })
  if [[ "$OUTPUT" == *"ERROR"* ]]; then
    echo "MySQL container is not available yet. Should not be long..."
    sleep 2
  else
    echo "MySQL is up! Moving on..."
  fi
done

OUTPUT=$(echo 'select * from users limit 1'|{ mysql --user=root --password=drupal --database=drupal --host=mysql 2>&1 || true; })
if [[ "$OUTPUT" == *"ERROR"* ]]; then
  echo "Installing Drupal because we did not find an entry in the users table."
  drush si -y --db-url=mysql://root:drupal@mysql/drupal \
    standard \
    install_configure_form.enable_update_status_module=NULL \
    install_configure_form.enable_update_status_emails=NULL
    # Previous line gets rid of error because of no SMTP server. See
    # drush help si for details.
  drush en -y devel webform_submission_change_history
else
  echo "Assuming Drupal is already running, because there is a users table with at least one entry."
fi
echo "Deployment of site OK; setting permissions for the file directory"
mkdir -p /var/www/html/sites/default/files
chown -R www-data:www-data /var/www/html/sites/default/files
