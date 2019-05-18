-- SUMMARY --

Extends the Raven Sentry client module.
Every errors are tagged with the raven_release variable.
You can then filter issues in sentry on this release tag.

For a full description of the module, visit the project page:
  http://drupal.org/project/raven_release

To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/raven_release


-- REQUIREMENTS --

Raven module.


-- INSTALLATION --

* Install as usual, see http://drupal.org/node/895232 for further information.


-- CONFIGURATION --

* Add this line to sites/default/settings.php to use the VERSION 
    environment variable as release tag.
  
  $config['raven_release.settings']['version'] = $_ENV['VERSION'];

  Then you have to set the environment variable in your php/http server, 
  see your vendors' documentation.

-- CONTACT --

Current maintainers:
* Stéphane Cottin - http://drupal.org/u/kaalh
