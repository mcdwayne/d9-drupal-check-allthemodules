Cookiebot
=========

## INTRODUCTION

This module offers Drupal integration for the third party Cookiebot service.
Cookiebot helps make your use of cookies and online tracking GDPR and ePR
compliant.

Note: Neither Cookiebot or this module will out-of-the box prevent cookies to be
placed on your website or prevent other tracking technologies to run. However,
the JavaScript events or special HTML attributes of Cookiebot can be used by a
developer to achieve exactly that.

More info about Cookiebot at https://www.cookiebot.com/en/help

## REQUIREMENTS

First of all, you will need a Cookiebot account with a configured domain.
You can register via one of this module's supporting organisations to support
development of this module.

[Sign up!](https://manage.cookiebot.com/goto/signup?rid=0N85W)
(Synetic's referral)

Then, grab your _Domain Group ID_ (CBID) from the 'Your scripts' tab.

## INSTALLATION

Install this as any other Drupal module
([help](https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules)).

When using composer, run the following command from your project root:

`composer require drupal/cookiebot`

Then either use `drush en cookiebot` from the command line or enable the module
via the 'Extend' admin interface.

## CONFIGURATION

1. Visit /admin/config/cookiebot
1. Set your _Domain Group ID_.
1. You can optionally display the full cookie declaration on a specific node
   page or place our block via admin/structure/block (Layout Builder supported).

### Cookiebot renew

To allow users to change cookiebot settings, you can add a menu link with
URL "/cookiebot-renew" or a link anywhere with a class `cookiebot-renew`.

The Cookiebot interface will show on click.
