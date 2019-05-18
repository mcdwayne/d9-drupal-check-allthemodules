
Field Formatter Filter
======================

Extends text field formatter settings to allow you to choose a different 
"text format" or text filter at the same time as the normal choices of 
"full" or "summary".

Use case
--------

Use it to

* Create super-simple teasers by removing block elements and headings.
* Use special enhancement filters like toc, tabs, glossary or chunker
  only in very specific view modes or for specific content types - in a way
  that does not require the editor to manage the text formats.
  
Usage
-----

Once installed, additional settings can be found when editing an entity types
 field settings, eg at `/admin/structure/types/manage/page/display`
 Where you choose "Default", "Trimmed", or "Summary or trimmed", on a longtext
 field, there will now be **"Additional Text Filter"**  options 
 in the format settings for that field.

Quickstart
----------
* Given a "page" content type, with a WYSIWYG editor in place
  for managing full-body content, 
  and a permissive **"Full HTML"** or **"WYSIWYG markup"** filter in use.
* Make a page that includes lots of scary markup in the first few paragraphs;
  embedded images, h2, lists, alignments, blockquotes.
* Visit a place that displays a 'teaser' of your page 
  (eg promote the page then visit `/node`). 
* Observe that it looks pretty inconsistent with whatever you expect
  teasers to behave like.

### To resolve this

* Enable this module and its requirements. 
  Ensure core **"Field UI"** is enabled.
* Visit `/admin/config/content/formats/` and add a text format
  called **"Safe teaser markup"** or whatever label makes sense to you.
* On that format, Use **"Limit allowed HTML tags"** and reduce the allowed set
  to remove anything that will throw off the layout. 
  Leave only minimal rich-text (inline level elements, and maybe 'p') behind. 
  `<a>` `<em>` `<strong>` `<p>` `<br>` is enough to start with.
* Visit **Administration : Structure : Content types**, 
  and **"Manage display : Teaser"**, eg for your **"Page"**.
* The **Body: Format** should probably be **"Summary or trimmed"**
  (the default),
  but now open up the Format settings.
* Now you can apply an **"Additional text filter"**, so choose 
  **"Safe teaser markup"**.
* Save the format setting, and save the teaser display.
* Visit your teaser page again and see that sanity has returned 
  to your teaser layouts.
* ... revise your filter rule to be as permissive or strict 
  as your theme requires.
 
Caveats
-------
Running two text filters one after the other can have unexpected side effects,
so it's recommended that the per-filter format be pretty simple.

*The normal per-node text format filter will always be run first*
 so security filters are still in place.
 This module provides a display tweak runs an *additional* process
 as part of the rendering.

Remainder display mode for text-with-summary fields
===================================================

Additionally, there is a new field_formatter for the text-with-summary 
field type. 'Remainder after trimming' can be used to display just the
leftovers of a body text area after a teaser summary has been removed.
It's the reciprocal of 'Summary or trimmed'

Use case
--------
The formatter may be used to create more elaborate layouts - eg with 
display_suite to show the body teaser in one region and the body remainder in 
another, possibly split by an image or for alignment.

Usage
-----
To make that work, you may use display_suite extras to create a 'dynamic field'
that duplicates the node body field, and lets you use teaser and anti-teaser 
as displays. 
You will need to ensure that the trim length in both cases is identical.
