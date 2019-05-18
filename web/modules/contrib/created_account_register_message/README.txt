INTRODUCTION
------------

The Created Account Register Message module display message when a user tries to
register an account which already has been created but never been used.

The module also sends an email to the user with a reset-link to reset her
password.

This is useful when the admin create an account and the user never use the link
sent by the admin, the user later will try to register and now will receive a
message telling her that the account already exists and she just needs to set
the password.

Before Drupal would trigger a "This email is already in use" error.

REQUIREMENTS
------------

None.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-modules
   for further information.

This project has been sponsored by: Agaric. https://www.drupal.org/agaric


