-- SUMMARY --

Demo Site is a simple module that allows you to take a snapshot of a Drupal
demonstration site. It turns a Drupal installation into a sandbox that you can
use for testing modules or publicly demonstrating a module / extension / theme
(you name it). In short: With cron enabled, the Drupal site will be reset to
the dumped state in a definable interval. Of course you can reset the site
manually, too.
Demonstration site module help to create snapshots of your drupal site. This
will save the current version of your site and you can restore to this version
later.

Two Options of taking snapshots :-
(1) Database Snapshots</b> Takes the snapshot of the whole database.All files
will get stored in the private directory. On resetting, All the configuration
settings and nodes will get restore.
(2) Configuration Snapshots</b> Takes the snapshot only of the configuration
settings of your drupal site. All files will get stored in the private directory.
On resetting the drupal site, this will only configuration setting will be
restored. Nodes will remain as it is.

For a full description visit the project page:
  http://drupal.org/project/demo
Bug reports, feature suggestions and latest developments:
  http://drupal.org/project/issues/demo


-- REQUIREMENTS --

* Cron.


-- INSTALLATION --

* Check if settings.php have the setting enabled ->
  $settings['file_private_path'] = 'sites/default/files/private';

* Copy the Demo module to your modules directory and enable it on the Modules
  page (admin/modules).

* Optionally configure who is allowed to administer Demo module, create dumps
  and reset the site on the Permissions page (admin/people/permissions).


-- CONFIGURATION --

* Create private directory for demo module if it doesn't exist in
  'sites/default/files/private/demo/'

* Configure the path where dumps will be stored at the Dump settings
  (admin/structure/snapshots).

To configure automatic reset:

* Go to Manage snapshots (admin/structure/snapshots/demo) and select a snapshot
  for cron.

* Enable atomatic reset from Dump settings
  (admin/structure/snapshots/demo/settings). Make sure you have cron configured
  to run at least once within the entered time interval.


-- USAGE --

* Go to Create snapshot (admin/structure/snapshots/demo) and create your first
  snapshot.

* After a while, reset your site (admin/structure/snapshots/demo/reset).


-- CONTACT --

Current maintainers:
* Daniel F. Kudwien (sun) - dev@unleashedmind.com
* Stefan M. Kudwien (smk-ka) - dev@unleashedmind.com

This project has been sponsored by:
* UNLEASHED MIND
  Specialized in consulting and planning of Drupal powered sites, UNLEASHED
  MIND offers installation, development, theming, customization, and hosting
  to get you started. Visit http://www.unleashedmind.com for more information.
