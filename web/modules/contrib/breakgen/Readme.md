# Breakgen

Check the [breakgen](https://github.com/tamtamnl/Drupal-Breakgen) repository for latest versions.

## Requirements
Breakgen has no requirements and support all default core image styles out of the box. Breakgen provides
example/extension module for integration with other contrib modules.

## Description
The Breakgen module provides a new drush command (`drush bg`) which generates image styles based on your theme `breakpoints.yml` file

## Example breakgen.breakpoint.yml file
Please check or modify your own theme breakpoint.yml file. Or directly use the `breakgen.breakpoint.yml` inside your project. 

## How-to generate
Run the command `drush bg breakgen`   

Please note if you define your own *.breakpoints.yml file please clear **the cache** before running the `drush bg` command.
Clearing the cache will read the breakpoints.yml file again after modifications.

## Mapping responsive images
There is an example module called `breakgen_responsive_images` that serves as an example responsive image style generator.

## Configuration Example
Breakgen extends the already existing drupal breakpoint config by adding the `breakgen` key.
All configuration for breakgen should be done under this key.

``` yml

breakgen.mobile:
    label: mobile
    mediaQuery: '(max-width: 479px)'
    weight: 0
    multipliers:
        - 1x
    breakgen:
        16x9_scale: # Breakgen group name
            responsive_image: true # breakgen_responsive_image extension mapping.
            responsive_image_fallback: true # breakgen_responsive_image extension mapping.
            percentages: # Percentage deviation mapping, allows you to resize the original.
                - 66.666666667%
                - 50%
                - 33.333333333%
            style_effects: # Style effects as array, these are mapped 1:1 from a style effect configuration.
                - id: image_scale
                  data:
                    width: 320
                    height: 240
        16x9_crop: # Breakgen group name for seconds group.
            percentages: # Percentage deviation but for seconds group.
                - 66.666666667%
                - 50%
                - 33.333333333%
            style_effects: # Style effects for seconds group.
                - id: crop_crop
                  weight: -10
                  data:
                    crop_type: '16:9' # Crop type mapping for image_widget_crop
                - id: image_scale_and_crop
                  weight: 0
                  data:
                    width: 320
                    height: 180

```
