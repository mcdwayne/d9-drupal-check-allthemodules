Import posts from Facebook, Twitter, and Instagram as Drupal content.

## Why this module?

`social_migration` uses Drupal's [Migrate API](https://www.drupal.org/docs/8/api/migrate-api/migrate-api-overview) to retrieve posts from social media providers, giving you a 100% Drupal-native means of interacting with those platforms. This in turn means compatibility with a number of other APIs and modules, such as Views, Configuration Manager, and Features.

## Installation

via composer: `composer require drupal/social_migration`

via direct download: place the extracted file in the `modules/contrib` folder.

## Dependencies

* migrate_plus:4.x
* migrate_tools:4.x

## Documentation

Once the module is enabled, three new content types will be created, one each for Facebook, Twitter, and Instagram. The module also provides configuration screens for entering or modifying API information.

**Important**: the API information for each of the social media platforms must be set up separately. Please see the following links for instructions for those platforms:

* [Facebook](https://developers.facebook.com/docs/apps/register)
* [Twitter](https://developer.twitter.com/en/docs/basics/authentication/guides/access-tokens)
* [Instagram](https://www.instagram.com/developer/register/)