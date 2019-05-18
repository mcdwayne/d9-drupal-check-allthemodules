
## Installation

1. Install via composer using:
composer config repositories.drupal composer https://packages.drupal.org/8
composer require drupal/hipchat

This will install the module and the hipchat PHP library dependency.

Note, if you install the module manually by copying to sites/all/modules, you will need to run:
composer require drupal/composer_manager

Then enable and init the composer manager module.

Then run:
composer drupal-rebuild


2. Add an admin API Token from HipChat and the default room to receive
   messages at /admin/config/services/hipchat

## Bugs and Feedback

Post bugs and feedback to https://drupal.org/project/issues/hipchat

D8 version: https://www.drupal.org/u/rjjakes