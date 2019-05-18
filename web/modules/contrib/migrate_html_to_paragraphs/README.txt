CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

"Migrate HTML to Paragraphs" is an API module which allows you to turn HTML
into Paragraph items using Migrate.
Migrate plugins can be assigned to a field using YAML declarations like any
other Migrate Plugin.
New plugins can be created easily by extending the plugin classes provided by
this module.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/migrate_html_to_paragraphs

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/migrate_html_to_paragraphs


REQUIREMENTS
------------

This module requires the following modules:

 * Migrate (Drupal Core)


RECOMMENDED MODULES
-------------------

 * Migrate Plus (https://www.drupal.org/project/migrate_plus):
   Extends the core migration framework with additional functionality.

 * Migrate Tools (https://www.drupal.org/project/migrate_tools):
   The Migrate Tools module provides tools for running and managing
   Drupal 8 migrations.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

For general documentation about how to write migration YAML files, see the
Migrate module and/or Migrate Plus module.

Specific configuration about how to implement the
"HTML to Paragraphs"-plugin, see below:

  field_which_will_contain_paragraph_items:
    plugin: html_to_paragraphs
    source: content_with_html
    parser:
      -
        plugin: html_parser_img
      -
        plugin: html_parser_iframe
    process:
      -
        plugin: html_process_img
        bundle: image
        field_name: field_image
        source_base_path: '/path/which/contains/the/files'
        source_base_url:
          - 'http://www.example.com'
          - 'http://example.com'
        target_folder: 'public://migrate/legacy/path/to/store/files'
      -
        plugin: html_process_iframe
        bundle: embed
        field_name: field_embed_codes
        text_format: embed_codes
      -
        plugin: html_process_text
        bundle: text
        field_name: field_text
        text_format: full_html
        fallback: true


MAINTAINERS
-----------

Current maintainers:
 * Jochen Verdeyen (jover) - https://drupal.org/user/310720
