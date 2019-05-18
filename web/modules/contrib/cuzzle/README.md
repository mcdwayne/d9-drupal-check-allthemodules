# Cuzzle for Drupal

[Cuzzle](https://github.com/namshi/cuzzle) is a PHP library that formats Guzzle
HTTP requests as a cURL command. With this module, all outbound HTTP requests
are logged in a way that can be copied and run locally.

## Installation

1. Use Composer to download this module.
1. Set `cuzzle_enabled` to `TRUE` in `settings.php`. Since the logs will
   contain any HTTP credentials, this is only recommended on development or
   local environments.
1. Enable the module.
1. Verify requests are being logged by triggering an update check at
   `/admin/reports/updates`. The request to `updates.drupal.org` will be
   shown in the system log.
