[![Build Status][buildimage]][buildurl]

[buildimage]: https://travis-ci.org/FreelyGive/decoupled_auth.png
[buildurl]: https://travis-ci.org/FreelyGive/decoupled_auth

# Decoupled User Authentication

Project site:  https://www.drupal.org/project/decoupled_auth

Code: https://www.drupal.org/project/decoupled_auth/git-instructions

Issues: https://drupal.org/project/issues/decoupled_auth

## Description

This module aims to provide a simple API for storing information about users
who visit your site whether they register or not. By extending the base user
entity the module allows you to store users that do not have a name and
password.

### Why bother?

Drupal has thousands of useful contributed modules and many of these work with
users really well (e.g. Simplenews, OG and Profile). These modules would be
even more effective if they could be used with users that are not registered.

For example, storing profiles for unregistered users opens up whole new
possibilities for using Drupal as a CRM framework.

Drupal Commerce could also benefit from this by allowing users to give an email
address but not register before going through the checkout process.
