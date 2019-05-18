INTRODUCTION
------------

The Shared E-mail module overrides the 'user' module's validation,
that prevents the same e-mail address being used by more than one user.

Works for both registration and account updates.

Displays a warning to the user that they are using a shared email.

Based on [Allowing Multiple Accounts from the Same E-Mail Address?
](http://drupal.org/node/15578#comment-249157)

All this module does is remove the unique constraint for the email using a hook.

REQUIREMENTS
------------

None.

INSTALLATION
------------

Install as usual, see [Installing contributed modules
](https://drupal.org/node/895232) for further information.

CONFIGURATION
-------------

1. Navigate to settings form through `Admin > Configuration > People > Shared
E-Mail Settings`

   or directly at path `/admin/config/people/shared-email`
