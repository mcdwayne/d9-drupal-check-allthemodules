# Thumbor Effects Crop

## Introduction

This module makes it possible to create crops in combination with the Thumbor
Effects module.

## Requirements

- [Thumbor Effects module](https://www.drupal.org/project/thumbor_effects)

## Installation

Install this module as any other Drupal module, see the documenation on
[Drupal.org](https://www.drupal.org/docs/user_guide/en/extend-module-install.html).

## Configuration

### Widget

Change the form field formatter of your form display to
`Image (Thumbor Effects Crop`).

### Image styles

Create or modify your image styles at `admin/config/media/image-styles` to use
the `Thumbor Effects Crop` effect. Height is used as fallback when no crop
is selected. The width and aspect ratio are used to calculate a new height.


### Crop types

This module requires a crop type with the id `thumbor_effects_crop`. It is
installed by default.

## Known limitations

- Cropping is currently only done by providing a fixed aspect ratio per file.
- This only works for Thumbor cropped files served via Drupal.
- Only one crop per file is supported, image style and media view mode is
supported due to limits of the Crop API and Drupal core. See this
[issue](https://www.drupal.org/node/2617818).

## Thanks to

* [Synetic](https://www.drupal.org/synetic) for providing time to work on this
  module.
* [Daniël Smidt](https://www.drupal.org/u/dmsmidt), for creating the first
  version.
