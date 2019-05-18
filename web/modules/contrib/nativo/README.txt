== Introduction ==

Nativo integrates third-party Nativo ads into a Drupal website.

=== How it works ===

This module defines a simple page at /promoted. This page's content
consists of placeholders, which Javascript from Nativo replaces with
real content.

Along with this, Nativo's Javascript is printed on each front-facing page, so
Nativo's system can inject ads into the DOM. See Configuration for options on
disabling whether this JS loads at all, as well as excluding it on certain
paths.

== Installation ==

Enable module, and grant permission "Administer Nativo settings" appropriately.

== Configuration ==

Configure at admin/config/search/nativo.
