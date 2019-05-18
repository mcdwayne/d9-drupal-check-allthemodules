Migrate report
==============

Description
-----------

Migrate report is a tiny module that generates a reports based on the last
migration run. Many times the migration and content managers are requesting a
list of errors, warnings and notices raised during migration. Migrate is logging
such information in the {migrate_message_*} tables but there's no aggregated
view on all errors and inconsistencies.

The Migrate report module generates a plain .txt file with all the messages
registered during the last migration, by compiling all information from the
key-value store and from the {migrate_message_*} tables. The reports can be
generated via UI or via Drush.

Usage
-----

After installing, the destination directory, where the reports will be stored,
can be configured at /admin/reports/migrate/config. It's possible to configure a
stream wrapper as teh destination but we don't recommend to store the reports
under the web tree, from where can be publicly accessed. At
/admin/reports/migrate the user is able to generate a report and see the list of
reports already generated.

The report generation can be triggered also with Drush:

$ drush migrate-report-generate

or

$ drush mrg

The configured destination path can be overridden by passing the --destination
option:

$ drush mrg --destination=/path/to/reports
