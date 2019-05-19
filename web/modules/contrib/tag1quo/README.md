## Overview

This module sends information about your website to Tag1 Consulting as part of
their Quo offering. It tracks which modules and themes you have in your
codebase, watching for Drupal LTS notifications and upstream releases to ensure
that any applicable fixes are available to your site in a timely manner. With
this module properly installed and configured, Tag1 will notify you whenever
security patches and patches should be applied.

Data is securely sent to Tag1. This requires that [cron](https://www.drupal.org/cron)
is configured on your website (<>), and that [OpenSSL](http://php.net/manual/en/openssl.installation.php)
support for PHP is properly installed.

For more information, visit: <https://quo.tag1consulting.com>.

## Installation

Repeat these steps for all Drupal websites you've paid for the Quo service
from Tag1 Consulting. To purchase support for additional websites, contact
your reseller or email support@tag1consulting.com.

1. Install the module.
   Extract the provided tag1quo.tar.gz compressed tarball into the appropriate
   modules directory (for example: sites/all/modules).

2. Enable the module.
   Visit 'Administration > Modules' at admin/modules and enable the 'Tag1 Quo'
   module.

3. Configure the module. Visit
   `Administration > Site configuration > Development > Tag1 Quo` at
   `/admin/config/development/tag1quo` and enter the token provided by Tag1
   Consulting. Then click `Save configuration`.

# Troubleshooting

Be sure that you configured your Token correctly (step #3 above). If you see an
error when configuring the token, visit `Recent log entries` (`/admin/reports/dblog`)
and look for messages from the `tag1quo` module. If there's not enough
information in the logs, go to the tag1quo configuration page
(`/admin/config/development/tag1quo`) and enable `Debug logging` in the
`Advanced` section. Then try to save the token again.  Finally, review the
`Recent log entries` once again.

If the problem is not obvious, email [support@tag1consulting.com](mailto:support@tag1consulting.com)
for help.
