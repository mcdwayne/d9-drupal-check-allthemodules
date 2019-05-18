Field Pager
===========

This module allow to use multiple values of field to create multiple pages.
For example, paginated pages for a single node. 

##Current state of development

The module is under development, please do not add issues for
coding standard, misspellings,  ... but for the real issues.

## Currently
  * Can use for content entities.
  * Can use for entity revisions (Paragraph for example).
  * Can use for Text fields.
  * Can use for Image fields.


## Installation and Configuration

Download via composer and Install via Drush
`composer require 'drupal/field_pager:1.x-dev'`
`drush en field_pager -y`

Rebuild cache to update drupal plugins
`drush cr`

__Configuration (Examples)__

If necessary, Add or Update a field (Text field, or Entity reference field)
to your content type with 'Allowed number of values' (On Field settings)
grater than 1 (Or Unlimited).

Change the display plugin on 'Manage Display'->'Format'.
Note : The default pager plugins are always named like "Xxxxx (Pager)"
