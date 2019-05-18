INTRODUCTION
------------

The Route Path Rewrite module changes the paths of configured routes. The routes are configured with the Drupal route name (described as **machine name** in the [structure of routes documentation](https://www.drupal.org/docs/8/api/routing-system/structure-of-routes)), for example the login: **user.login**.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/route_path_rewrite

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/route_path_rewrite

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
 
 * Configure the routes that should be rewritten in the *route_path_rewrite.settings.yml* configuration file (Currently not editable in the UI).
 
    - Use the following format:
    ```yaml
    routes_to_rewrite:
      # The name of the key, here "user_login" does not matter, example config for the user login:
      user_login:
        name: 'user.login'
        new_path: '/backend-login'
      user_login_http:
        name: 'user.login.http'
        new_path: '/api-backend-login'
    ```

MAINTAINERS
-----------

**Current maintainers:**
 * Orlando Thöny - https://www.drupal.org/u/orlandothoeny

**This project has been sponsored by:**

 * **Namics** - Initial development
 
   Namics is one of the leading providers of e-business and digital brand communication services in the German-speaking region. The full-service agency helps companies transform business models with top-quality interdisciplinary solutions, promising increased, measurable success for their clients. Visit https://www.namics.com/en for more information.
