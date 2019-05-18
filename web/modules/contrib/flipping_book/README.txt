
-- SUMMARY --

This module provides an integration point between Drupal8 and FlippingBook
(http://flippingbook.com/). Flippingbook is a very used software to make digital 
publication from any document you have (.doc, .pdf...).

In Drupal 8, Flipping books are entities. Once the module has been installed, go
to "/admin/structure/flipping-book" to create a new Flipping Book bundle choosing
a label and the import location (eg. private or public files directory).

If you choose "Private folder", remember to grant "Access private Flipping Books"
permission to the roles allowed to see the flipping books.

A list of all flipping books is available at "/admin/content/flipping-book".

Flipping books can be referenced via core Entity Reference field, by choosing
"Other" from the entity types selection list.

-- GOALS AND LIMITATIONS --

-- Available Field Display formats

1) Flipping Book Iframe
2) Flipping Book Link

-- CONTACT --

Current maintainers:

* bmeme.com - http://www.bmeme.com
