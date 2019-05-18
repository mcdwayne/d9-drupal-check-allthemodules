Commerce Variation Add-on
-------------------------

CONTENTS OF THIS FILE
---------------------

-   Introduction
-   Requirements
-   Installation
-   Configuration
-   Troubleshooting
-   Maintainers

INTRODUCTION
------------

Commerce Variation Add-On allows Drupal Commerce Product Variations to
reference other Product Variations and automatically add them to the
cart, and includes the ability to lock the quantity to the parent
quantity.

For example, if you'd like every purchase of a blue t-shirt to come with
a blue pen, this is the module for you.

REQUIREMENTS
------------

This module relies on Drupal Commerce to function.

-   https://drupal.org/project/commerce

INSTALLATION
------------

Install this as you would any other Drupal.org module - Composer is
recommended:

    composer require drupal/commerce_vado

CONFIGURATION
-------------

-   Primary configuration can be found at Administration » Commerce »
    Configuration.

-   Use this menu to add the required fields to your product variation
    types. Don't worry about filling the fields out on all your
    variations, VADO is smart enough not to bother with empty fields.

-   Setting add on variations can be done while editing a product
    variation (after enabling the fields)

TROUBLESHOOTING
---------------

Posting to the issue queue is a safe bet, but ensure you've enabled the
fields in the configuration and within the form display for the product
variation type before doing so.

MAINTAINERS
-----------

Current maintainers:

-   Gabriel Simmer (gmem) - https://drupal.org/u/gmem
    -   Primary maintainter
-   Josh Miller (joshmiller) - https://drupal.org/u/joshmiller
    -   Support/review, secondary maintainer

This projects has been sponsored by:

ACRO MEDIA INC

Acro Media is a Drupal Commerce agency redefining the online retail
experience and frees organizations from the limitations of restrictive
proprietary platforms. By leveraging Drupal and Drupal Commerce, we
empower businesses to adapt technology for their existing business
systems and create ideal experiences for their customers.
