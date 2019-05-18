CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers


INTRODUCTION
------------

The DocCheck Basic module allows users to authenticate Drupal resources through
the DocCheck login (basic license) mechanism.

The module only adds a login mechanism for one user. It does not set any
permissions or block access.

 * For a full description of the module visit:
   https://www.drupal.org/project/doccheck_basic

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/doccheck_basic


REQUIREMENTS
------------

This module requires an account with DocCheck CReaM:

 * https://crm.doccheck.com/com/

"With the DocCheck login system you can authenticate healthcare professionals
visiting your websites and grant them access to protected pharmaceutical
content. The online platform DocCheck CReaM offers everything for implementing
the DocCheck login into your websites. You can create and configure new logins
and access statistics to learn more about the visitors to your websites."


RECOMMENDED MODULES
-------------------

 * User protect (https://www.drupal.org/project/userprotect): locks user edit
   page


INSTALLATION
------------

Install the DocCheck Basic module as you would normally install a contributed
Drupal module. Visit https://www.drupal.org/node/1897420 for further
information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the DocCheck Basic module.
    2. Navigate to Administration > Configuration > People > DocCheck Basic to
       edit settings.
    3. Enter the DocCheck Login ID.
    4. Select a Template size: S, M, L, XL or enter custom template name.
    5. Select an user. Users with "Administrator", "Anonymous user" and
       "Authenticated user" only roles are excluded. An additional role is
       required. Please ensure that this role has view rights for the
       protected pages.
    6. Save configuration.

Block visibility, content type view and node view permissions have to be
set up for the selected drupal role.

Security advisory: please restrict the permissions of this user/role to the
absolute minimum! All permissions exceeding "access published nodes" and "use
search" should be considered carefully. You should protect the edit page of the
selected drupal user. Every DocCheck user has full access with the permissions
of the selected drupal user.


TROUBLESHOOTING
---------------
 * Make sure that no webserver redirection is set for the target URL (e.g.
   http -> https)

 * DocCheck block, page, and callback page (target URL) cannot be cached,
   because a cookie is set to save the URL of protected content page.

 * Make sure that you configure the login at DocCheck login management
   platform https://crm.doccheck.com/com/ correctly. The login status should
   not be "locked". Company logins must be set up in the same company as the
   login.

 * It is not possible to login directly from DocCheck (listed "websites
   with doccheck" section). Users have to visit a protected node first.


FAQ
---

Q: How do I disable development mode with Drush?

A: drush config-set config.doccheck_basic dc_devmode 0


Q: How do I overwrite the DocCheck Login ID (e.g. 1234567890) with Drush?

A: drush config-set config.doccheck_basic dc_loginid 1234567890


MAINTAINERS
-----------

 * sleitner - https://www.drupal.org/u/sleitner
