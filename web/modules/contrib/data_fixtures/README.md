# Drupal 8 Data Fixtures module

The fixtures module allows you to easily create dummy content.

**Requirements**:
* drush

**Installation**
* Install and enable the module
* Implement a Generator interface (see Basic concepts for more info)
* run ```drush fixtures-load``` inside a working drupal installation

**Road Map**
* User interface for all available commands
* Run only a certain set of generators

## Disclaimer

This module was built with a certain workflow in mind. It might work well for you. 
It might not. Feel free to open any issues if you feel it missing features.

This module **is not** production ready. It will never be this. It is intended for **local development only**. 
Optionally, you can use it to create working test-environments on the fly with dummy content.

## Basic concepts

Each module defining an Entity, ConfigEntity, custom structure, ... that relies on Content to be testable (e.g. an entity bundle, menu items, ...) can also define Generators.
These generators will run by this module to automatically create a working Drupal environment from scratch.

This is epscially useful if your CI/CD workflow includes creating entirely new environments on the fly without any existing content.
This can be due to contract limitations, privacy-related data like e.g. user accounts or the fact that it is still under development.

We also found it is useful when having a team consisting of "Drupal developers" working together with Front-end developers.
When defining content types, user profiles, custom entities, ... we also provide Generators that generate content for these structures.
This way, a front-end developer does not have to manually create content every time he works on a new feature.

## When not to use it
Moving content between environments: Data fixtures is based on the idea we can simply Truncate the db if needed.
Deploying real content to production: Data fixtures is based on random data. Let's keep it that way

## Generators

Each Generators needs to implement the ```Drupal\data_fixtures\Interfaces\Generator``` interface.
It has 2 methods: ```load``` and ```unload```.

To register a Generator, add it to your services.yml file and tag it with "data_fixtures"
```
tags:
    - { name: data_fixtures }
```

## Available commands:
* drush fixtures-load -> runs the load method on all Generators.
* drush fixtures-unload -> runs the unload method on all Generators.
* drush fixtures-reload -> runs fixtures-unload and fixtures-load, in that order.
