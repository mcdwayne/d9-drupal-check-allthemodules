# CONTENTS OF THIS FILE

 * Introduction
 * How to
 * Requirements
 * Maintainers


## Introduction

Do you want to build a running website straight from your repository, but you realized that you have to get some dummy
content from somewhere? Search no more. This module will give you an API to program your own content generators, that
you will be able to run with one command, and fill your website with content required either for development
or presentation.

This module is different from [data_fixtures](https://www.drupal.org/project/data_fixtures) in that it aims to mimic as
much as it's reasonably possible [DoctrineFixturesBundle](https://symfony.com/doc/master/bundles/DoctrineFixturesBundle/index.html).

It was made with a workflow of programmatically building your website from scratch in mind, and tries to avoid any
ambigious states, that's why it deletes all content before loading any fixtures (don't worry, it will warn you :) ).

Great match with Docker, if you are looking for ultimate automation.

## How to

This module has API very similar to [DoctrineFixturesBundle](https://symfony.com/doc/master/bundles/DoctrineFixturesBundle/index.html),
with some Drupal-specific differences/simplifications.

### Basics

Minimally, your fixture class has to implement `FixtureInterface`, and be registered as a service tagged with a `content_fixture`
tag.

### Sharing objects between fixtures.

The easiest way of achieving this is to extend `AbstractFixture` class, that will provide you with some additional
sugar - this abstract class implements `SharedFixtureInterface` that will give you a way of sharing created objects
between fixtures.

### Order of execution of fixtures

For this to work, you have to be able to decide on the order of execution of fixtures, and you can do
this in two ways:

1. By implementing `OrderedFixtureInterface` - this will allow you to assign a value to each fixture, that will be used
   for ordering.
2. By implementing `DependentFixtureInterface`, that will allow you to declare dependencies between fixtures, and order
   of execution will be calculated by using this information.

### Groups

You can also implement `FixtureGroupInterface` in your fixture, to assign it to some custom groups. It will allow you
to run fixtures by groups they belong to. This way you can have different set of fixtures for presentation, different
for development etc.

### Execution

You need `drush` to run your fixtures. Module provides you with two commands:
* `content-fixtures:list`
* `content-fixtures:load`

See: `drush help content-fixtures:list` and `drush help content-fixtures:load` .

Happy coding!

## Requirements

* PHP >= 5.6
* Drush 8 / 9

## Maintainers

* Łukasz Zaroda <luken@luken-tech.pl>
