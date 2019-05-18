-- SUMMARY --

The Patreon User module uses the Patreon module to allow users to log in with
their Patreon account and/or clone their Patreon account to the Drupal
website as a linked account (with its own password).

-- REQUIREMENTS --

The module requires the Patreon module and its dependencies to be enabled and
correctly configured.

-- INSTALLATION --

The module can be installed as usual; see https://drupal.org/node/895232 for
further information. It is a sub-module of the Patreon module.

-- CONFIGURATION --

A new section is added to the user configuration page at
admin/config/people/accounts to allow admins to configure the module. The
options for log in are:

1. Disabled log in via Patreon.
2. Allow all users to log in via Patreon.
3. Allow only current patrons to log in via Patreon.

Admins can also choose whether Patreon users must set a Drupal password (and
subsequently log in as usual after their account has been created) or to
allow direct log in via Patreon.

The module provides a new block to present a log in form to Patrone users.

-- CUSTOMIZATION --

This module provides functionality to allow Drupal user accounts to be cloned
from Patreon accounts. Subsequentially, these accounts can then be treated as
any other Drupal user account, with permissions and access set as required. The
module also creates two roles - patreon_user and deleted_patreon_user - that
are automatically assigned depending on the user's current status on Patreon or
the Drupal site. Additional roles are also created for each pledge level on the
Patreon campaign. Additional fields are also created to store the user's Patreon
id and tokens.

These can be used in custom code as required to provide additional
functionality, or used in combination with other Drupal user modules.

The module has been updated to throw various Exceptions in the event of failure.
These must be correctly handled in any custom code.

-- TROUBLESHOOTING --

The Patreon module must be correctly configured before this module will
provide any functionality. If you have problems, check the status report to
ensure you have the correct configuration.

If you receive an error connecting to the API, ensure that the callback URL
<your site URL>/patreon_user/oauth has been added to the allowed calledback URLs
on the Patreon site.

-- CONTACT --

Current maintainer:

* Dale Smith (MrDaleSmith) - https://drupal.org/user/2612656