Commerce Quantity Pricing
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

Commerce Quantity Pricing allows you to directly couple the quantity of
a product to the price a customer pays, based on Taxonomy term. Very
similar to how price lists work, but tied directly to a product term to
make it easier to modify an entire swath of similar products.

e.g If you sell 100 envelopes, you might want to have the customer pay
\$10, but if they buy 150, you want them to pay \$8.

REQUIREMENTS
------------

This module relies on Drupal Commerce to function.

-   https://drupal.org/project/commerce

INSTALLATION
------------

Install this as you would any other Drupal.org module - Composer is
recommended:

    composer require drupal/commerce_quantity_pricing

CONFIGURATION
-------------

Currently proper configuration pages are WIP.

-   To enable the field, head to the Taxonomy vocab you'd like to apply
    it to and add the "Quantity Pricing" field, named the same
    ("Quantity Pricing", machine name "field\_quantity\_pricing" is what
    really matters).

-   Next go to the order item type you want to apply the special
    dropdown to and under quantity set the formatter to "Quantity
    Pricing".

TROUBLESHOOTING
---------------

Posting to the issue queue is a safe bet, but ensure you've enabled the
fields required. More than happy to add to the README with anything
missed (wip module means some changes will occur)!

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
