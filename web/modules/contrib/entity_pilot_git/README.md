# Entity Pilot Git

This module is designed to modify the transport mechanism of the [Entity Pilot](https://drupal.org/project/entity_pilot) module in order to export and import content from the file system

## Set up
It is recommended to install this module via composer in order to automatically install all dependencies.

Follow [this documentation](https://www.drupal.org/node/2718229) in order to set up your site to install Drupal modules via composer.

Then: `composer require drupal/entity_pilot_git`

Following this, you'll need to set up a Entity Pilot Account. Our module only uses this in order to piggy back on some of Entity Pilot's code. **Entity Pilot Git does not encrypt or transmit your content via the Entity Pilot API.**

Go to Structure > Entity Pilot > Accounts and add an account.

Due to [an issue in Entity Pilot](https://www.drupal.org/node/2693945) we need to overwrite the secret that is stored in config so our module can function correctly.

Export the entity pilot account in to your site's config directory and manually edit the secret key to be a regular hex string instead of binary.

Import the updated account configuration.

You are now ready to use the console commands to import and export content!

## Usage

### Config

export_directory - this is used as a the export/import root and should be a relative path to the Drupal root.
skip_entity_types - a list of entity types to skip when exporting.

### Commands

There are drupal console commands in the module that can be used to to export and import content.
 
To get more information on these commands run:

````
drupal entity_pilot_git:export --help
drupal entity_pilot_git:import --help
 
````

## Known issues

If there are services on your site that render a node automatically when it is created, for example search_api's automatic indexing, you will need to turn the service off before importing. See https://www.drupal.org/node/2871892

You can do this in your import script:
````
drush ev "\Drupal::configFactory()->getEditable('search_api.index.default')->set('options.index_directly', false)->save();"
drupal entity_pilot_git:import ....
drush ev "\Drupal::configFactory()->getEditable('search_api.index.default')->set('options.index_directly', true)->save();"

````
