# Formassembly module

## Overview

Synchronize FormAssembly.com forms to Drupal nodes and leverage the best of
both worlds. Forms may either be embedded in Drupal content using Entity
Reference Fields or displayed on their own path.

Forms may be set to have default values and if the Token module is enabled,
these values can dynamically use Drupal data via tokens.

Submitted forms display either a thank you message or redirect to another
another page. When embedded, the embedded form is replaced with the thank
you message for a clear user experience.

## Dependencies

### Drupal

This module requires the `map_widget` module.

### External

Two external dependencies via composer: `fathershawn/oauth2-formassembly` which
manages the OAuth interaction. `symfony/dom-crawler` which is used for parsing
the returned html for insertion into Drupal's page build.

### Optional:

- `scrivo/highlight.php`: Allows form html to be displayed for inspection on
   the entity edit form.
- `gajus/dindent`: If you use scrivio/highlight, adding this library will make
    the form html easier to read.
- `drupal/token`: Use tokens to send drupal data as pre-filled form parameters.
- `drupal/key`: Securely store your oauth credentials
