CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers

INTRODUCTION
------------


REQUIREMENTS
------------
The Drupal project on which Micro Site is enabled must be accessible from
a fully qualified domain name (or a local domain for development purpose).
Otherwise, if using 127.0.0.1 or localhost, Micro Site will not work and and
can produce fatal errors.

INSTALLATION
------------
Install as you would normally install a contributed Drupal module. See:
https://www.drupal.org/documentation/install/modules-themes/modules-8
for further information.


CONFIGURATION
-------------

Once Micro Site is enabled you should configure immediately settings for
Micro Site module. In particular, you must immediately configure the three
parameters base_url, base_scheme, public_url. The absence of these parameters
can lead to a 404 page on the master instance's home page.

You can override theses settings in the settings.php file. For example :

@code
$config['micro_site.settings']['base_url'] = 'microsite.local';
$config['micro_site.settings']['base_scheme'] = 'http';
$config['micro_site.settings']['public_url'] = 'microsite.local';
@endcode

Each micro site create automatically (when created)  a file in the directory
DRUPAL_ROOT/sites/default/hosts with the corresponding trusted_host_patterns
setting.

You have to add to the settings.php file the following lines in order to include
these files.

@code
$dir_hosts = $app_root . '/' . $site_path . '/hosts';
if (file_exists($dir_hosts) && is_dir($dir_hosts)) {
  foreach (glob($dir_hosts . '/*.host.php') as $filename) {
    include $filename;
  }
}
@endcode


TROUBLESHOOTING
---------------


FAQ
---


MAINTAINERS
-----------

Current maintainers:
 * flocondetoile - https://drupal.org/u/flocondetoile
