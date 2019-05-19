- Getting started
This module must be installed into /modules/contrib. After installing this module, visit /admin/reports. You should see a new report called Sitelog. When first installed, the report will be empty.

- Cron
For this module to work correctly, you must add a command that will run a
cron job at midnight, for example:

0 0 * * * wget -O - -q -t 1 http://www.example.com/cron/<key>

For more details about creating cron jobs, see:
https://www.drupal.org/docs/7/setting-up-cron-for-drupal/configuring-cron-jobs-using-the-cron-command.

In 'Cron settings' at /admin/config/system/cron, set 'Run cron every' to 'Never'.

You may also want to set your server time to your local time zone.

-- Searches
To see data for searches, you must enable 'Log searches' at /admin/config/search/pages.

-- Statistics
To get referral and visitor statistic, you must add this line:

RewriteCond %{REQUEST_URI} !/modules/contrib/sitelog/sitelog.php$

into the root .htaccess file. Add it directly below this line:

RewriteCond %{REQUEST_URI} !/core/modules/statistics/statistics.php$

--- Smart IP
To see data for the statistics map, you must install the Smart IP module from https://www.drupal.org/project/smart_ip.
