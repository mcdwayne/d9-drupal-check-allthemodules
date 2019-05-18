#Paragraphs Collection Demo

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
The Paragraphs Collection Demo module adds various behavior plugins and paragraph types.. This project is a collection of EXPERIMENTS to
provide plugins for a rich variety of paragraph types. The following behavior
plugins are provided or in progress:

## Accordion
Displays collapsible paragraphs content panels.

## Anchor
Allows setting ID attribute to paragraphs used as jump-to links.

It offers a text field on the paragraph instance form, where the user can
choose a machine name. The entered value, prefixed with "scrollto-", will be
set as the paragraph element ID used as jump position in URLs, for instance:
http://examples.com/landingpage#scrollto-XYZ

## Background Image
Allows setting an image to be used as background for the paragraph with
pre-defined effects. One of the following options can be chosen:
  - Fixed background
  - Parallax effect background (TBD)

## Lockable
Allows a paragraph instance to be locked by an administrator. Only users with
the proper permission can edit the locked paragraphs.

## Slideshow
Allows converting any paragraph type with an entity reference field to a slider.

It uses the [Slick](https://www.drupal.org/project/slick) module to build slide
functionality. To get it working properly, you will need to install it and its
related module dependency. Also the [Slick JS library](https://github.com/kenwheeler/slick/)
is needed. The assets are at:
  - *<root>/libraries/slick/slick/slick.css*
  - *<root>/libraries/slick/slick/slick-theme.css* (optional if a skin is chosen)
  - *<root>/libraries/slick/slick/slick.min.js*

The ```paragraphs_collection_demo``` submodule contains an example where it
creates a Slider paragraph type that enables the Slider plugin and has two Text
paragraph type used as slides.

In each paragraph type configuration form, if the Slider plugin is enabled,
it is possible to select a paragraph type field to use as slide and the
Slick optionset(s) to apply on the slides. The selected field used as slider
must have a cardinality greater than 1. Each Slick optionset is listed as a
checkbox and is fetched from the slick module (including the tree sort).
To have more slick options, go to the Slick UI config page (/admin/config/media/slick).

REQUIREMENTS
------------
This module requires the following modules:

 * Paragraphs (https://www.drupal.org/project/paragraphs)
 * Slick (https://www.drupal.org/project/slick)
 * Block field (https://www.drupal.org/project/block_field)

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
