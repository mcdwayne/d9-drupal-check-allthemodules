
= AsciiDoc filter =

This module provides AsciiDoc filter integration for Drupal input
formats.

== Features ==

* Input filter to generate XHTML based on AsciiDoc syntax.

To submit bug reports and feature suggestions, or to track changes:
  http://drupal.org/project/issues/asciidoc

== Requirements ==

* External packages, either:
** AsciiDoc: http://www.methods.co.nz/asciidoc/index.html
** Asciidoctor: http://asciidoctor.org

== Instalation ==

* Install as usual, see http://drupal.org/node/895232 for further
information.

== Configuration ==

. Set up a new input format or add AsciiDoc support to an existing format at
   Administer >> Site configuration >> Input formats

. For best security, ensure that:
  .. The HTML filter is after the Markdown filter on the "Reorder" page of
     the input format.
  .. Only markup you would like to allow in via AsciiDoc is configured to
     be allowed via the HTML filter.

== Credits ==

The authors of AsciiDoc.

Current Maintainer: Marco Villegas (marvil07)

// vim: set syntax=asciidoc
