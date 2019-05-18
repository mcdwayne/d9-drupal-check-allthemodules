EMAIL CONFIRMER USER
====================

CONTENTS OF THIS FILE
---------------------

 * Summary
 * Installation
 * Configuration
 * Usage
 * Contact


SUMMARY
-------

Email confirmer user is a sub-module from the email confirmer suite that
provides user email address related functionality:

 * confirmation request when a user changes their email address
 * update the database of confirmed emails when a users logs in for the first
   time or by a one-time log in link


INSTALLATION
------------

Install as usual, see https://www.drupal.org/node/1897420 for further
information.

Email confirmer user defines a permission to allow selected roles to not
require confirmation of the new email address when they change it.

Also note that the "access email confirmation" permission, defined by the
base email_confirmer module, is required by users to confirm, cancel or resend
an email confirmation.


CONFIGURATION
-------------

Module configuration is available under Manage -> Configuration -> System
-> User email confirmation settings (admin/config/system/email-confirmer/user).


USAGE
-----

On user email change

Default configuration enables the confirmation of the new email address when a
user updates it. The new address is temporarily stored and the original address
will remain in the user's profile until the confirmation is done.

When a change of email address is pending confirmation, the user will see a
message in the description of theemail field of the user's edit form. The new
address is shown, as well as a link to resend the email confirmation.

Note that by default no confirmation is required to set the previous email
address again, or any other email address that the user has previously confirm.

There is a permission to bypass the user email change confirmation.

On user log in

By default, the email address of new users is considered confirmed thank's to
the Drupal core on-register confirmation or by another custom mechanism. A new
record is created the first time a user logs in or when a one-time login link
is used. This allows to keep updated the database of confirmed addresses.
Both options can be enabled or disabled in the module settings.


CONTACT
-------

Current maintainers:
* Manuel Adan (manuel.adan) - https://www.drupal.org/user/516420
