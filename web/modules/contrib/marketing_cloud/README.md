CONTENTS OF THIS FILE
=====================

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Usages
 * Troubleshooting
 * Faq
 * Maintainers


INTRODUCTION
============

This suite of modules supports provides services to all of the Salesforce
marketing cloud APIs, and validates all payloads against Json Schema. The
following API resources are available in sub-modules:

 * Address
 * Assets
 * Campaigns
 * Contacts
 * Data Events
 * Interaction
 * Messages
 * Platform
 * Push
 * SMS

This module is intended for module developers.


REQUIREMENTS
============


CORE
----

 * [Drupal Core >= 8.5](https://www.drupal.org/project/drupal)


3rd Party
---------

These should be automatically installed by composer when you install this module.

 * [Swaggest PHP Json Schema](https://github.com/swaggest/php-json-schema)


RECOMMENDED
-----------

 * [Markdown](https://www.drupal.org/project/markdown)

This is used to render the module info pages from the markdown code. If not
installed, you will still be able to view the info pages, but they will be
rendered as plain text.


OBJECTIVES
==========

There are a lot of API calls available in the Salesforce Marketing Cloud API.
This module aims to provide all of them as a service in one place.

The different sections of API have been separated into sub-modules, to allow
lighter weight in Drupal and for developers using this as a sub-module, to
select which services they want to be available.

The JSON body objects in API calls can often be very complex and potentially
infinite depth. Therefore, a decision was made early on in the development to
not provide service functions that have individual input variables and then
render the JSON from that. Instead, it is up to the developer to produce the
JSON object, and the service will then validate the JSON object against the
schema to make sure that it meets minimum requirements.

The schema can be edited and validated, and Json validation can we switched
on/off in the settings.


SIMILAR PROJECTS
================


SALESFORCE
----------


This suite integrates with Salesforce by asynchronously synchronizing Drupal
entities (E.g., users, nodes, files) with Salesforce objects (E.g., contacts,
organizations, opportunities).

However it uses Salesforce OAuth, which is an entirely different endpoint to
Marketing Cloud, and requires different credentials.


EXACT TARGET API
----------------

Provides abstraction of the ExactTarget XML API for use by other modules. By
itself, this module provides no functionality and should only be installed if
another module requires it as a dependency.


SUPPORT
=======

Updates to the Json Schema are welcome.

A community documentation page is available on
[www.drupal.org/docs/8/modules/marketing-cloud](https://www.drupal.org/docs/8/modules/marketing-cloud). Please
add your notes on solving issues and configuring the module there.

Please search the issue queue before filing an issue, and update to latest
development release to make sure your problem has not already been fixed.
Issues filed using the issue summary template will receive priority over other
issues.


MAINTAINERS
===========

 * [john_a](https://drupal.org/user/2573976)
