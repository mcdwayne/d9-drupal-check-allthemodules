CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Purest allows you to easily create tailored rest responses for nodes,
taxonomies, menus and users. We quickly grew tired of writing custom rest
resources and normalizers to suit each project's specification.

Using easy to understand forms you can rename keys or exclude any base field
and any custom field attached to nodes, taxonomy terms, menu items and users
making it very quick to create tailored rest responses.

We believe headless Drupal is the way forward but writing a ton of boilerplate
for every project quickly becomes tedious so we created Purest so we can spend
more time focusing on aspects of projects that do require unique custom
resources and functionality.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/purest

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/purest


FEATURES
--------

 * Content resource that resolves entities by path alias
 * Menu resource that returns a menu by machine name
 * Menus resource that returns multiple chosen menus
 * User resources to provide registration, account activation & password reset
   resources
 * Recaptcha service
 * Custom serializer to add pagination to views
 * UI to rename field keys and exclude fields from rest responses for node
   types, taxonomy vocabularies, menu items and users


REQUIREMENTS
------------

This module requires following modules outside of Drupal core:

 * REST UI - https://www.drupal.org/project/restui
 * Token - https://www.drupal.org/project/token


INSTALLATION
------------

 * Install the Purest module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Web Services > Purest to
       configure Purest settings.
    3. Set the URL of the front end application.
    4. Set the path prefix.
    5. Choose whether to use the Use Purest Typed Data Normalizer. Purest
       includes a typed data normalizer. It simplifies the structures returned
       for nodes and taxonomy terms in rest responses.
    6. Save configuration.


MAINTAINERS
-----------

 * circularone - https://www.drupal.org/u/circularone
