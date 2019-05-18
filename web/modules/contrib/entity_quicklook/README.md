Entity Quicklook
================

CONTENTS
--------

-   Introduction
-   Requirements
-   Recommended modules
-   Installation
-   Configuration
-   Troubleshooting
-   Maintainers

INTRODUCTION
------------

The Entity Quicklook module will provide a field formatter for entity
reference fields. When rendered it will create a link that when clicked
uses ajax to open a modal where a specific view mode is used to render
the referenced entity.

For a full description of the module, visit the project page:
http://drupal.org/project/entity\_quicklook

To submit bug reports and feature suggestions, or to track changes:
http://drupal.org/project/issues/entity\_quicklook

REQUIREMENTS
------------

-   The latest version of [PHP
    7](https://www.drupal.org/docs/8/system-requirements/php-requirements)
    that Drupal supports.

INSTALLATION
------------

-   Install as you would normally install a contributed Drupal module.
    See:
    https://drupal.org/documentation/install/modules-themes/modules-8
    for further information.

        composer require drupal/entity_quicklook

CONFIGURATION
-------------

Configuration is all done through the field formatter.

1.  Add an entity reference field to the "parent" entity of your choice.
2.  Go to "Display Settings" and set the entity reference format to
    "Entity Quicklook"
    -   Additional options for how to display the link and modal are
        found by clicking on the cog icon
3.  Go to your "parent" entity and set the entity reference field

MAINTAINERS
-----------

-   Stephanie Galata - https://www.drupal.org/u/sgalata
-   Gabriel Simmer - https://www.drupal.org/u/gmem
