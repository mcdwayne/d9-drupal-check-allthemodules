Clockpicker
===========

This module integrates the Clockpicker library with Drupal.

It provides

- a custom form element
- an additional option to core's 'datetime' form element to use the clockpicker
  widget
- a setting on core's 'Date and time' and 'Date and time range' widgets to use
  the clockpicker in the time portion.

## Setup

Download the Clockpicker library from http://weareoutman.github.io/clockpicker/
and install it as a Drupal library, that is, in libraries/.

## Configuration

Date fields must be set to use the 'Date and time' or 'Date and time range'
widget. Edit the field's settings and enable the 'Use the clockpicker for times'
option.
