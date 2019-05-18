Microformats Module
===================

Microformats module for Drupal 8 incorporates indieweb/php-mf2 3.0+ PHP library:
https://github.com/indieweb/php-mf2/

Composer and symfony autoloader incorporated in Drupal 8 make microformat functions
available to any module. The microformats library can fetch and parse remote strings
and output structured PHP objects.

This module is under active development since May 2016.

Microformats are an important general syntax for specifications like h-card,
h-entry and webmentions. By leveraging microformats and other PHP libraries already
available at https://github.com/indieweb/ it should be possible to implement many
of these formats in Drupal with minimal code duplication.

php-mf2 info
============
From the Readme: https://github.com/indieweb/php-mf2/blob/master/README.md

php-mf2 is a pure, generic microformats-2 parser. It makes HTML as easy to consume as JSON.

Instead of having a hard-coded list of all the different microformats, it follows a set of
procedures to handle different property types (e.g. p- for plaintext, u- for URL, etc).
This allows for a very small and maintainable parser.

- To fetch microformats from a URL, call `Mf2\fetch($url)`
- To parse microformats from HTML, call `Mf2\parse($html, $url)`, where `$url` is the URL
from which `$html` was loaded, if any. This parameter is required for correct relative URL
parsing and must not be left out unless parsing HTML which is not loaded from the web.


More module info
================

Module URL:
https://www.drupal.org/project/microformats

Please submit issues here: https://www.drupal.org/project/issues/microformats

Mark external library issues as "Code-External Libraries" category.

Look here for more microformat parser libraries: https://github.com/indieweb/

Microformats and the Indieweb
============================

Microformat info: http://microformats.org/
- Wiki: http://microformats.org/wiki/Main_Page

IndieWebCamp related development: https://indiewebcamp.com/microformats

Source: https://indiewebcamp.com/microformats

microformats are extensions to HTML for marking up people, organizations, events, locations,
blog posts, products, reviews, resumes, recipes etc. Sites use microformats to publish a
standard API that is consumed and used by search engines, aggregators, and other tools.

The IndieWeb makes heavy use of:

h-card to mark up profiles and authors in published posts, then consumed by code
(e.g. reply-contexts, readers) for authorship and more.

h-entry to markup posts, replies, etc., then consumed by code for displaying, 
summarizing, replying.

in-reply-to to markup links from replies to original posts, then consumed by code for 
displaying comments, comment threads, etc.

XFN for relationships, and identity consolidation (rel=me), consumed by code for
IndieAuth etc.

Developers
==========

Microformats for Drupal 8 by Dan Feidt ( https://drupal.org )

Sitewide Contact Info for Drupal 8 by Karthikeyan Manivasagam ( https://www.drupal.org/u/karthikeyan-manivasagam )

Originally developed by Benjamin Doherty ( https://drupal.org/u/bangpound )
for Drupal 5.x 2006-2007

The Indieweb community ( https://github.com/indieweb )
