CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Solution
 * Configuration
 * How it works
 * Global use

INTRODUCTION
------------

It's quite common to have your site protected with Basic authentication on
the development environments so your site is not publicly accessible from the
outside world. On the other hand you also may be using basic_auth module
to provide your users an alternative option how to authenticate against your
site. Typical use-case may be using REST services where users are authenticated
via Basic authentication.

However such a use case turns out to be inproperly working as Drupal will try to
authenticate all of the users coming via Basic authentication. Even those who
are just used for site protection and are not related to Drupal site in any way.
Such an authentication results in Access denied on all of upcoming Drupal pages.

SOLUTION
--------

Solution to this problem may be actually pretty simple. All we need to do is
just tell Drupal to skip Basic authentication for users not related to the site.

CONFIGURATION
-------------

The basic_auth_limited module is applying the solution from former paragraph.
You may configure a regular expression pattern for usernames. Users who match
the pattern will be then also authenticated against Drupal site. Users who
do not match will be allowed to let it, since they are considered as simple
site protection users not related to the site.

HOW IT WORKS
------------

It is achieving this goal by introducing custom Http Middleware where it just
listens for Basic authentication to happen. Once such an auth attempt happen
it will decide based on the configured regular expression pattern, if the
user should be also authenticated against Drupal site or not. If username
matches the pattern then the request goes as it would go normally. If username
does not match the pattern then the module simple removes `PHP_AUTH_USER`
variable from request so the request looks like a normal request for the rest of
the code execution. It will make the regular authentication provider from
basic_auth module not to apply (since it checks for `PHP_AUTH_USER` variable).

GLOBAL USE
----------

Basic authentication is for one request only. If you need to be logged in
permanently and access every site, then you need to make Basic authentication
to be global. You may achieve this by installing
[basic_auth_global](https://www.drupal.org/project/basic_auth_global) module.
