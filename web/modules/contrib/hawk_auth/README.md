Hawk Authentication for Drupal
===========================

Introduction
------------
> Hawk is an HTTP authentication scheme using a message authentication code
> (MAC) algorithm to provide partial HTTP request cryptographic verification.
> — [hawk README][0]

This module implements Hawk authentication protocol for Drupal giving an
alternative schema for authentication.

Requirements
------------
The module requires PHP-Hawk library loaded, can be installed using
composer_manager module.

* composer_manager (https://drupal.org/project/composer_manager)

Recommended Modules
------------
* REST (Built-in with Drupal 8)
  Can be used alongside Hawk authentication for REST requests

* REST UI (https://drupal.org/project/restui)
  Can be used to easily configure REST

Installation
------------

* Put the module in drupal/modules directory
* Install composer_manager module and load the dependencies
  (see [composer_manager's documentation][1] for more details)
* Enable Hawk Auth from Admin > Extend under Web Services section
* Grant required users permissions from Admin > People > Permissions to
  create hawk credentials
* Now the permitted users can generate hawk credentials from
  User > Profile > Hawk Credentials

Usage example
------------
Hawk can be used with Drupal's REST services module amongst other things,
the example here is one of the applications.

Also see examples/example.php for a demo

* Enable REST module (optional: get REST UI module from drupal.org for easier
  management).
* Enable one of the routes (/node/<nid> as an example) and enable hawk_auth as
  an authentication provider for the route and methods.
* Create a hawk credential from Profile > Hawk Credential, the ID here will be
  the Credential ID for Hawk.
* See [Dragooon/php-hawk][2] docs for more information on how to make client
  side requests
* Perform GET and POST requests at /node/<nid> using Credentials generated from
  the profile area.

[0]: https://raw.githubusercontent.com/hueniverse/hawk/master/README.md
[1]: https://www.drupal.org/node/2405811
[2]: https://github.com/Dragooon/php-hawk/blob/master/docs/Getting%20Started.md
