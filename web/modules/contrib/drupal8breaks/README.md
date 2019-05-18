# drupal8breaks

This is a response to https://drupal.stackexchange.com/q/273371/57183

## Problem:

How to allow DrupalBreaks to go through the "Restricted HTML" text filter?

## Answer:

Override the FilterHTML plugin with a class that extends it and make sure all <!--break--> comments are retained in the html.

## How to use:

Activate the module.

## GitHub link:
https://github.com/stefanospetrakis/drupal8breaks

## Inspiration:
https://medium.com/@djphenaproxima/how-to-bend-drupal-8-plugins-to-your-nefarious-will-94da0c31f095
