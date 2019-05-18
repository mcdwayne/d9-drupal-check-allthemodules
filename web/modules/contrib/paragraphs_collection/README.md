#Paragraphs Collection

THIS PROJECT IS EXPERIMENTAL. DO NOT USE IT IN PRODUCTION PROJECTS. THERE IS NO
UPGRADE PATH UNTIL A BETA RELEASE.

CONTENT OF THIS FILE
--------------------
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers

INTRODUCTION
------------
The Paragraphs Collection module introduces a powerful plugin system to attach
behaviors to paragraph types. This project is a collection of EXPERIMENTS to
provide plugins for a rich variety of paragraph types. The following behavior
plugins are provided or in progress:

## Grid layout
Provides a pre-defined grid layouts system to be applied to paragraph instance.
It offers a way to specify grid layouts that can be reused. It introduced a new
type of YAML configuration, where layouts can be customized, their definitions
will be discovered and used as choices to be applied to paragraphs.
See *paragraphs_collection.api.php* for more information.

On each paragraph type configuration form, the user can decide which group of
grid sets will be displayed per paragraph type. A layout option can be then
selected on the paragraph instance form. The paragraph item will be shown in a
grid fashion.

## Lockable
Allows a paragraph instance to be locked by an administrator. Only users with
the proper permission can edit the locked paragraphs.

## Style
Provides a CSS styles collection to be applied to individual or group of paragraphs.
It offers the ability to discover style definitions in all enabled modules and
themes, as a choice to be applied.

Each module can have its own style for paragraphs defined as YAML based
configuration schema. See *paragraphs_collection.api.php* for more information.

## Visibility per language
Allows specific paragraphs to be hidden for certain languages.

For each individual paragraph entity, a set of languages can be selected. For
these languages the paragraph entity is not displayed. (Alternatively, you can
chose to show the paragraph exclusively for the selected set of languages.)

If you have a "container" paragraph which uses the Grid layout or the Accordion
plugin, its child paragraphs should not use the Visibility per language plugin.
Using it will result in holes in the grid and missing titles and/or content in
the Accordion, respectively. Other "container" behavior plugins may be similarly
affected.

In order for this plugin's UI to work correctly, you will need to download the
required files of the [Select2](https://select2.github.io/) library and copy
them into your Drupal installation. You will need:
  - \<Drupal_8_(web)_root\>/libraries/select2/dist/js/select2.full.min.js
  - \<Drupal_8_(web)_root\>/libraries/select2/dist/css/select2.min.css

REQUIREMENTS
------------
This module requires the following modules:

 * Paragraphs (https://www.drupal.org/project/paragraphs)

INSTALLATION
------------
Please check the module dependencies and eventually the README.md files of the
related modules to properly install everything you need.

CONFIGURATION
-------------
Each plugin can be configured on each paragraph type configuration form, for
example: */admin/structure/paragraphs_type/example_paragraph_type*. Enable
a behavior plugin to apply its functionality to the related paragraph type.

FAQ
---
1. How to install libraries in D8?
  To install an external library, read the Install Libraries API documentation:
  https://www.drupal.org/docs/7/modules/libraries-api/installing-an-external-library-that-is-required-by-a-contributed-module

MAINTAINERS
-----------
Current maintainers:
 * Primoz Hmeljak (Primsi) https://www.drupal.org/u/primsi
 * Miro Dietiker (miro_dietiker) https://www.drupal.org/u/miro_dietiker
 * Sascha Grossenbacher (Berdir) https://www.drupal.org/u/berdir
