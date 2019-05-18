# Bootstrap Pages

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers

INTRODUCTION
------------

The Bootstrap Pages module provides a suite of Content Types, Taxonomies, and
Views to work with the Bootstrap Paragraphs module.

**Content Types**

  * Article
  * Author
  * Landing Page

The content types are fully configured to have proper Meta tags and Schema.org
markup; optimized, responsive images, preconfigured friendly URLs; and an
organized administration.  Once installed, you have control over changing what
is in configuration, and overriding templates and styles in your theme.

**Taxonomies**

  * Category
  * Topic
  * Type

Taxonomies can be used in conjunction with articles to create different views. A
 view block that takes a term is included.

***Examples:***
Type: Blog, News, Press Release
Category: General, Fun, Code
Topic: Open Tagging

**Views**
  * Articles
  * Terms

Two views are included that can be added to pages or articles using the View
Paragraph bundle.  Pass an argument to filter!

REQUIREMENTS
------------

This module requires the following modules:

  * [Bootstrap Paragraphs](https://www.drupal.org/project/bootstrap_paragraphs)
  * [ctools](https://www.drupal.org/project/ctools)
  * [Field Group](https://www.drupal.org/project/field_group)
  * [ImageAPI Optimize](https://www.drupal.org/project/imageapi_optimize)
  * [Imagemagick](https://www.drupal.org/project/imagemagick)
  * [Image Effects](https://www.drupal.org/project/image_effects)
  * [Inline Entity Form](https://www.drupal.org/project/inline_entity_form)
  * [Metatag](https://www.drupal.org/project/metatag)
  * [Pathauto](https://www.drupal.org/project/pathauto)
  * [Scheduler](https://www.drupal.org/project/scheduler)
  * [Schema.org Metatag](https://www.drupal.org/project/schema_metatag)
  * Bootstrap framework's CSS and JS included in your theme

INSTALLATION
------------

  * Install the module as you normally would.
  * Verify installation by visiting /admin/structure/types and seeing
  your new Content types, Article, Author, and Landing Page.

CONFIGURATION
-------------

  * On each Content type, click Manage fields and choose which Text
  formats to use.
  * Configure you Paragraph types by visiting /admin/structure/paragraphs_type
  * On the Simple and Blank bundles, click Manage fields and choose which Text
  formats to use.  We recommend a *Full HTML* for the Simple, and a
  *Full HTML - No Editor* for the Blank

MAINTAINERS
-----------

Current maintainers
  * [thejimbirch](https://www.drupal.org/u/thejimbirch)
