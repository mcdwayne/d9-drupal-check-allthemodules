INTRODUCTION
------------

The Route Basic Authentication module protects configured routes with
HTTP basic authentication.
The routes are configured with the Drupal route name
(described as **machine name** in the
[documentation](https://www.drupal.org/docs/8/api/routing-system/structure-of-routes)),
for example the login: **user.login**.
The HTTP methods  (GET, POST, PUT, DELETE, ...)
that should be protected by HTTP basic authentication
are also configured for each route. 
A site wide username and password is used,
configurable on the settings page
(Administration » Configuration » System » Route Basic Authentication settings).

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/route_basic_auth

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/route_basic_auth

REQUIREMENTS
------------

* This module requires Drupal 8.6 or above.
* No additional modules are needed.

INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.

CONFIGURATION
-------------
 
 * Configure user permissions in Administration » People » Permissions:

   - Change the Route Basic Authentication settings

     Permission to change the module settings.

 * Configure username and password on the settings page or
 *route_basic_auth.settings.yml* configuration file.
 
 * Configure whether or not flood protection should be enabled
 on the settings page or *route_basic_auth.settings.yml* configuration file.
 
 * Configure the routes that should be protected with
 HTTP basic authentication in the*route_basic_auth.settings.yml*
 configuration file (Currently not editable in the UI).
 
    - Use the following format:
    ```yaml
    protected_routes:
      # The name of the key, here "user_login" does not matter.
      user_login:
        name: 'user.login'
        methods:
          - 'GET'
      user_login_http:
        name: 'user.login.http'
        methods:
          - 'POST'
    ```

MAINTAINERS
-----------

**Current maintainers:**
 * Orlando Thöny - https://www.drupal.org/u/orlandothoeny

**This project has been sponsored by:**

 * **Namics** - Initial development
 
   Namics is one of the leading providers
   of e-business and digital brand communication services
   in the German-speaking region.
   The full-service agency helps companies transform business models
   with top-quality interdisciplinary solutions, promising increased,
   measurable success for their clients.
   Visit https://www.namics.com/en for more information.
