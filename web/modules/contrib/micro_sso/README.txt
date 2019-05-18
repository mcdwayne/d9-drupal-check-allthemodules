CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers

INTRODUCTION
------------
This module provide single sign-on (SSO) functionality from a master host and
the micro sites powered.

REQUIREMENTS
------------
Micro SSO module requires Micro Site.


INSTALLATION
------------
Install as you would normally install a contributed Drupal module. See:
https://www.drupal.org/documentation/install/modules-themes/modules-8
for further information.


CONFIGURATION
-------------
This module do not require configuration. Permission provided by this module
just must be configured.

And then, if a user is authenticated on the master host and visit a micro site
as an anonymous user, this user will then be automatically logged in under the
conditions below :

  - user's role must have the permission "Use SSO login on micro site"
  - user must be a member of the micro site

An user with the permission "Administer sites entities" will be always
automatically logged in.


TROUBLESHOOTING
---------------
This module seems to have issues with FireFox. This ajax request made on
the master host do not catch the session cookie, and therefor user is not
recognized as authenticated on the master.

@See https://www.drupal.org/project/micro_sso/issues/3044638


FAQ
---


MAINTAINERS
-----------

Current maintainers:
 * flocondetoile - https://drupal.org/u/flocondetoile
