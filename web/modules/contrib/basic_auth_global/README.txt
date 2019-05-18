This module alters the Basic Auth service so it can be used globally for
routes that do not specify an authentication method. It can be useful
on sites that use the Basic Auth module on a site where the web
server also implements basic authentication (typically a development
environment).

Configuration
=============
1. Adjust the user and password of the web sever's basic authentication
   so they match with a Drupal user.
2. Enable this module.
3. Visit the site in a browser. The browser, unless there is already
  a cookie, will prompt for user credentials. Enter them.
4. Authentication should pass and you should automatically be logged
  in as the Drupal user.

Limitations
===========

You can't log out because the user credentials are appended by the
browser on every request. Therefore, anonymous navigation is
not possible. If you need to test anonymous navigation, then work
out a way for not requiring Basic Auth module in your site (like
using a different authentication method like OAuth).
