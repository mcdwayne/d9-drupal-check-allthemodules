Entity Reference with Layout
============================

Paragraphs + Layout for Drupal 8

Overview
========

Entity Reference with Layout empowers content creators to design beautiful pages with structured content (aka Paragraphs). It borrows concepts from other paragraphs-inspired modules like Bricks, and provides authors with a way to visually manipulate referenced entities and create unique page layouts. Entity Reference with Layout leverages Drupal’s core Layout Discovery module.

Works with Paragraphs
=====================

While Entity Reference with Layout theoretically works with any referenced entity types, testing and development has so far been limited to paragraphs only. Interested in testing / extending this module to work with other entity types? We’d love to hear your thoughts, use cases, and experiences in the issue queue!

Current Status is Experimental
==============================

Entity Reference with Layout under active development and currently provides only an experimental dev release. Test heavily before using in production.

Installation
============

Install with composer:
composer require drupal/entity_reference_layout

Setup
=====

1. First, make sure you have a few paragraphs types configured for your site.
2. You’ll need an additional paragraph type for Entity Reference with Layouts to use for attaching layouts. Don’t worry if this seems unclear at first. Just create a new paragraph specifically for your layouts – for example, you might call your new paragraph type, “Section”. Adding fields for your new type is completely optional.
3. Decide which content type you want to use for creating layouts with paragraphs (for example, Basic Page).
4. Add a new field to the content type you chose in step 3. Pick “Paragraph with Layout” as the field type. Leave “Paragraph” as the type of item to reference, and “Unlimited” as the allowed number of values. Click “Save field settings”.
5. Configure your new field. Check out the configuration options below.
6. Go to Content > Add Content > [Chosen Content Type from Step 3], and start using the new field!
