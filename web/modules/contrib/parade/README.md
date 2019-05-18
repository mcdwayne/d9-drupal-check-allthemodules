[![Build Status](https://travis-ci.org/brainsum/parade.svg?branch=8.x-2.x)](https://travis-ci.org/brainsum/parade)

# README.md

Parade is a module to create one page sites from pre-customized paragraphs in your content.
It's based on https://www.drupal.org/project/paragraphs .

## INSTALLATION
### With composer
You need to add the following repositories to your composer.json:

    "drupal": {
        "type": "composer",
        "url": "https://packages.drupal.org/8"
    }

Composer can't resolve repositories of the dependencies, that's why you have to
use this workaround. After this, you just have to use "composer require
drupal/parade" to get the module and the dependencies, and "drush en parade" to
enable it in your site.

### Without composer
@todo


## CONFIGURATION

@todo

## Notes
The "locations" section type uses [Geocoder autocomplete](https://www.drupal.org/project/geocoder_autocomplete) which internally uses the Google Maps API.
That requires an API key to work. This is not yet possible with the module.
Follow [Geocoder autocomplete issue #2989952](https://www.drupal.org/project/geocoder_autocomplete/issues/2989952) for updates and possible patches for D8.

## ISSUES

@todo
