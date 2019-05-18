# Dialect

Minority matters! This module provides a simple way to redirect a subset
of languages to a single node.
The main use case is for defining secondary languages on your website
without having to translate the whole interface.

It avoids to have the website in a state that will contain almost no data
for these languages:
- a front page with untranslated content
- untranslated menus and terms
- ...

This subset of languages will be redirect from any path to a single node.

By default, the language switcher is displayed as a closed collapsible select
with the current language excluded, but the template can be easily overridden.
This option was chosen because the secondary languages name are displayed with
the node title instead of the language name to clearly separate the primary
and secondary languages.

Optionally the default links from the language switcher can be displayed with
the language code (EN) as a replacement of the language name (English).

## Configuration

- Install the module then place the 'Language switcher' block that appears
in category 'Dialect'.
- Configure the node in /admin/config/dialect/shared_block_config.
The language fallback configuration is node done via the block configuration
because several instances can be defined and we need to keep a consistent
configuration among blocks.

## Custom template

The template and default styling can be overridden without having to deal
with the cache tags.

If you want to customize styling, think about
[library overriding](https://goo.gl/UkXm3Q).
For styling, just override `dialect.module.css`.
By default, the dropdown is displayed on click, if you want to display it on
hover, think about overriding `dialect.js`.
