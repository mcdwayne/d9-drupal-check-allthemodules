Commerce Product Review
=======================

This module provides review feature for Drupal Commerce products.

Drupal Commerce is the leading flexible eCommerce solution for Drupal,
powering over 60,000 online stores of all sizes.

[Issue Tracker](https://www.drupal.org/project/issues/commerce_product_review?version=8.x)

## Installation

It is recommended to use [Composer](https://getcomposer.org/) to get this module
with all dependencies:

```
composer require "drupal/commerce_product_review"
```

See the [Drupal](https://www.drupal.org/docs/8/extending-drupal-8/installing-modules-composer-dependencies)
documentation for more details.

### rateit.js Javascript library

The [rateit.js](https://github.com/gjunge/rateit.js) Javascript library is
required for displaying both rating values and rating form as stars. There are
two ways to include the library:

**MANUAL INSTALLATION**

[Download the library](https://github.com/gjunge/rateit.js/releases) and extract
the file under the "libraries" directory. Ensure, that the library's js and css
files are found within the following path: libraries/jquery.rateit/scripts

**INSTALLATION VIA COMPOSER**

If you have already installed the module via Composer, we recommend to do the
same with the rateit.js library. However, manual steps are required in order to
install Javascript libraries correctly (in the required destination path) with
Composer.

We highly recommend to use [Asset Packagist](https://asset-packagist.org) in
order to load Javascript libraries via Composer.

First, **copy the repositories snippet** from the module's composer.json file
into your project's composer.json file.

Next, the following snippet must be added into your project's composer.json
file so the javascript library is installed into the correct location:

"extra": {
    "installer-types": [
        "bower-asset",
        "npm-asset"
    ],
    "installer-paths": {
        "web/libraries/{$name}": [
            "type:drupal-library",
            "type:bower-asset",
            "type:npm-asset"
        ],
    }
}

If there are already 'repositories' and/or 'extra' entries in your project's
composer.json, merge these new entries with the already existing entries.

After that, run:

```
composer require "bower-asset/jquery.rateit"
```

## Configuration

1. Install the module
2. Use and configure the shipped "default" review type or create your own types
   at /admin/commerce/config/product-review-types. On the edit page of a
   product review type, define which product types should be activated for the
   given product review type. You can also enter e-mail addresses being notified
   when a new product review has been entered.
3. Like for any other fieldable entity type in Drupal, you can optionally add
   your own fields, as well as change the form and view display definitions.
4. Adjust the permissions to your needs. By default, any user may view the
   published product reviews. Writing reviews is per default only allowed for
   authenticated users. While given create access to guest users should work as
   well, the module was not primarily developed for that use case.
   New product reviews won't get published automatically without admin approval,
   unless you assign the "**publish commerce_product_review**" permission to the
   affected user roles.

## Credits

Commerce Quantity Increments module was originally developed and is currently
maintained by [Mag. Andreas Mayr](https://www.drupal.org/u/agoradesign).

All initial development was sponsored by
[agoraDesign KG](https://www.agoradesign.at).
