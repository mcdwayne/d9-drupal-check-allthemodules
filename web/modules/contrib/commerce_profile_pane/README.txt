INTRODUCTION
------------

The Commerce Profile Pane module provides checkout panes for Drupal Commerce
which output a user's profile form.

REQUIREMENTS
------------

This module requires the following modules:

 * Commerce
 * Profile (also required by Commerce)
 * Inline Entity Form (also required by Commerce)

LIMITATIONS
-----------

For profile types which allow multiple profile entities per user, this loads
the first such profile found.

A profile pane can not be put in the 'login' step, as that does not provide a
submit button for the whole form.
