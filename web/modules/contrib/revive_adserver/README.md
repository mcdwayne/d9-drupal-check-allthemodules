# Revive Adserver
  
The Revive Adserver module provides a block and field, that render
[Revive Adserver](https://revive-adserver.com) Ad zones.

## Features

* Add Revive Adserver ads using a Drupal block plugin
* Attach Revive Adserver ads to any fieldable entity using a Drupal Field
* Choose between the following ads delivery methods
  * Asynchronous Javascript
  * iFrame
  * Javascript
* Make the delivery method configurable on per entity base (optional)
* Sync the ad zones from Revive

## Installation

1. Download with composer.
2. Enable the module.

## Configuration
1. Configure the module at `/admin/structure/services/revive-adserver`.
2. Optional: Sync the zones via the Revive API in the module configuration.

### Block
Add a block and set configure the to-be-rendered zone and delivery method.

### Field
Add a field to your designated content entity. Choose the delivery method in
the field display configuration.

If you like to let your content editors choose the invocation method on *per
entity base* you can enable the checkbox in the field's configuration. You
can limit the available methods in the "Manage fields" configuration.

## Requirements

* A composer-based workflow
* Block (Drupal core)
* Field (Drupal core)
