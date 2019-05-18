Commerce Approve
----------------

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

Commerce Approve gives sellers the ability to require a manual sign off
on order items within a cart before checkout can complete. Works on a
taxonomy-term basis and is intended to be used on a per-category sort of
use case. Works in a custom checkout pane that defaults to the Review
page of the checkout flow.

An ideal situation in which this module can be used would be if you sold
custom t-shirts, and you wanted to ensure customers have double checked
their custom t-shirt for spelling errors before buying the item.

REQUIREMENTS
------------

Requires Drupal Commerce.

-   https://drupal.org/project/commerce

INSTALLATION
------------

Installation is the same as any other Drupal module, although we
recommend Composer.

``` {.bash}
composer require drupal/commerce_approve
```

CONFIGURATION
-------------

Primary configuration can be found at Administration » Commerce »
Configuration, under "Manage Commerce Approve". You'll then be able to
configure whether a taxonomy term requires sign off, as well as change
the message displayed to the customer to explain the purpose of the
checkbox.

TROUBLESHOOTING
---------------

If you don't see the approval checkbox in the checkout pane, verify the
approval is attached to the correct taxonomy terms. Check watchdog for
any errors then post to the issue queue.

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
