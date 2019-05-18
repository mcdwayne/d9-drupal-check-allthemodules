MRR Entity Filter
-----------------
This is an addon to the Media Reference Revisions module that provides a text
filter that can render media objects referenced using the Entity Embed module,
while paying attention to the media object's specific revision.


Requirements
--------------------------------------------------------------------------------
This module has one primary dependencies:

* Entity Embed
  https://www.drupal.org/project/entity_embed
  Allows entities to be embedded in text fields using the standard core text
  formats. Provides a text filter which this module extends from.


Features
--------------------------------------------------------------------------------
* Provides a new "Display embedded entities (revision-locking)" text filter
  which loads the appropriate revision of media embedded in text fields using
  the Entity Embed system.


Known Issues
--------------------------------------------------------------------------------
* Only media objects are currently supported.
* The new text filter will be automatically installed to replace the normal
  "Display Embedded Entities" filter on any text formats which use it.
* The new text filter must not be used at the same time as the normal "Display
  Embedded Entities" filter; while technically possible there may be unintended
  consequences of the two running concurrently.


Credits / contact
--------------------------------------------------------------------------------
Currently maintained by Damien McKenna [1].

Ongoing development is sponsored by Mediacurrent [2].

The best way to contact the authors is to submit an issue, be it a support
request, a feature request or a bug report, in the project issue queue:
  https://www.drupal.org/project/issues/media_reference_revisions


References
--------------------------------------------------------------------------------
1: https://www.drupal.org/u/damienmckenna
2: https://www.mediacurrent.com/
