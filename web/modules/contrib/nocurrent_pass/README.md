# No Current Password

Drupal 8 port of the No Current Password drupal 7 contrib module.

Ported by David Czinege
2016-07-22

This module disables the "current password" field that has been added to Drupal 8's user
edit form, at user/%/edit.

When you enable this module, the current password field will be removed by default.
To enable the password field again, go to admin/config/people/accounts and uncheck the
"Do no require current password" checkbox.