CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Contact Tools module is a pack of tools for working with Drupal 8 core
Contact module forms.

Features:

 * AJAX support for contact forms on demand.
 * [Service][Service documentation] to easily call the contact form w/ and w/o AJAX support, generate the
   link, which opens form in modal window w/ and w/o AJAX.
 * [Text filter] which allows to create simple links with modal form support.
 * [Twig functions] to easy embed modal links or whole form in the template.
 * [Hooks] to modify data on every step.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/contact_tools
   or
   http://contact-tools.readthedocs.io/en/8.x-1.x/

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/contact_tools


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

It's recommended to install module via composer.

 * Install the Contact Tools module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. There is no UI for the module. These tools are targeted for developers to
       use on demand, not globally.
    3. Visit the external documentation page for more details.


MAINTAINERS
-----------

 * Nikita Malyshev (Niklan) - https://www.drupal.org/u/niklan

[Service documentation]: https://contact-tools.readthedocs.io/en/8.x-1.x/service/
[Text filter]: https://contact-tools.readthedocs.io/en/8.x-1.x/filter/
[Twig functions]: https://contact-tools.readthedocs.io/en/8.x-1.x/twig/
[Hooks]: https://contact-tools.readthedocs.io/en/8.x-1.x/hooks/
