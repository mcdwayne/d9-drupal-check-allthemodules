DEACTIVATE ACCOUNT
-------------------------------------------------------------------------------
This module allows a user to delete his/her account or deactivate it
temporarily. A user can choose the time period option for which the account
will stay deactivated. These options are configurable. The account will stay
inactive for the selected time period.

DEPENDENCIES
-------------------------------------------------------------------------------
None.

INSTALLATION AND CONFIGURATION
-------------------------------------------------------------------------------
1. Download the module and place it with other contributed modules
   (e.g. sites/all/modules/contrib).
2. Enable it from Modules list page.
3. Modify permissions on the People >> Permissions page.
4. Go to admin/config/people/deactivate-account, and set the time period
   options. These options will be presented to the user, after (s)he selects to
   deactivate the account.

FEATURES
-------------------------------------------------------------------------------
1. Configurable time period options for which user account can be deactivated.
2. Setting to either keep/disable the content (nodes, comments) after the
   account has been deactivated.
3. The "Deactivate account form" can either be shown at a normal menu link or a
   local tab under user profile. This option is configurable.
4. A menu path has to be provided if the "Deactivate account form" is set to be
   displayed as per menu.
5. The user can be redirected to a configurable path after the account is
   deactivated or deleted.

FAQ
-------------------------------------------------------------------------------
Q: Can admin deactivate account of any other user?

A: No, this module enables admin to set different time period options. from
   which a user can choose the time period option for which the account
   will stay deactivated.

MAINTAINERS
-------------------------------------------------------------------------------
Anand Toshniwal (anand.toshniwal93) - https://www.drupal.org/u/anandtoshniwal93
