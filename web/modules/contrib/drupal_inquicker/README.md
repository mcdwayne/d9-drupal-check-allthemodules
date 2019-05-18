Drupal Inquicker
=====

[![CircleCI](https://circleci.com/gh/dcycle/drupal_inquicker.svg?style=svg)](https://circleci.com/gh/dcycle/drupal_inquicker)

A Drupal 8 module which allows you to interact with the [Inquicker API v2](https://docs.inquicker.com/api/v2/).

This module provides no graphical user interface, caching or administration forms. It is for developers only.

Usage
-----

### Step 1: Make sure you have an Inquicker API key

### Step 2: Install as you would any Drupal module:

### Step 3: Add the following to your settings.php file:

    $config['drupal_inquicker']['sources']['default'] = [
      'url' => 'https://api.inquicker.com',
      'source' => 'live',
      'key' => 'my-api-key',
    ];

### Step 4: You can now obtain data from Inquicker

To fetch all location IDs:

    drush ev "print_r(inquicker()->responseListFormatter()->format(inquicker()->source('default')->rows('locations')))"

To fetch all detailed location information:

    drush ev "print_r(inquicker()->detailedResponseListFormatter()->format(inquicker()->source('default')->rows('locations')))"

To fetch all facilities:

    drush ev "print_r(inquicker()->responseListFormatter()->format(inquicker()->source('default')->rows('facilities')))"

To fetch all service lines:

    drush ev "print_r(inquicker()->responseListFormatter()->format(inquicker()->source('default')->rows('service_lines')))"

To fetch all schedules for a location and service line:

    LOCATION=my-location-id1,my-location-id2
    SERVICELINE=my-service-line-id
    drush ev "print_r(inquicker()->scheduleListFormatter()->format(inquicker()->source('default')->schedules(['locations' => '$LOCATION', 'service_lines' => '$SERVICELINE'])))"

To fetch all locations within a latitude/longitude:

    LAT=41.4620
    LON=-81.0737
    drush ev "print_r(inquicker()->responseListFormatter()->format(inquicker()->source('default')->rows('locations', ['latitude' => $LAT, 'longitude' => $LON])))"

Issue queue and pull requests
-----

Please use the [Drupal issue queue](https://www.drupal.org/project/issues/search/drupal_inquicker) for this project.

Please run tests by running `./scripts/test.sh` (you do not need to install or configure anything except Docker to run this) on your proposed changes before suggesting patches. Use [GitHub](https://github.com/dcycle/drupal_inquicker) for pull requests.

Development
-----

The code is available on [GitHub](https://github.com/dcycle/drupal_inquicker) and [Drupal.org](https://www.drupal.org/project/drupal_inquicker).

Automated testing is on [CircleCI](https://circleci.com/gh/dcycle/drupal_inquicker).

To install a local version for development or evaluation, install Docker and run `./scripts/deploy.sh`.
