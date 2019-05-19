Webform Digest
==============


Overview
--------

This module adds a daily digest email for webform submissions. These are
customised per webform and can optionally have conditions attached to
determine which submissions are flagged.


Installation
------------

This module defines a custom cron job so it can be determined how frequently
the cron job runs.

The pre defined setup is for it to run daily using Drupal's cron hook.

This could be disabled and a cron job setup pointing to
/admin/structure/webform_digest/send.

`$config['webform_digest.settings']['cron.enabled'] = FALSE;`

If you intend to override this to run daily or weekly you could override the
module settings to:

`$config['webform_digest.settings']['cron.frequency'] = 'hour';`
`$config['webform_digest.settings']['cron.frequency'] = 'day';`
`$config['webform_digest.settings']['cron.frequency'] = 'week';`

The module also has an hour set (default is 9) which is used to determine the
time after which the digest will be sent.

`$config['webform_digest.settings']['cron.hour'] = 9;`