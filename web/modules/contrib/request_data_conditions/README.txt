
CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Using the conditions
 * Maintainers


INTRODUCTION
------------

This module defines a set of conditions for use with the context module
(and others). All conditions are based on the HTTP request data and are
as follows:
 - Cookie
 - HTTP header
 - Query parameters (Ie.: ?param=something )
 - Session data

INSTALLATION
------------

Install as normal by copying to your /modules folder and enabling in
Drupal 8 admin: /admin/modules


USING THE CONDITIONS
--------------------

The conditions will be available for use by modules such as Context:
https://www.drupal.org/project/context

All conditions added by the module (Headers, Session, Query parameters,
Cookies) have the same fields available:
 - The name of the of cookie, parameters, header, session variable
 - the operator to search on ("must equal", "regular expression", etc)
 - the value to match by


MAINTAINERS
-----------

https://www.drupal.org/u/clivelinsell
https://www.drupal.org/u/rjjakes