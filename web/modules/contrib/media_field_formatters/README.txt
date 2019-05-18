Media Field Formatters
----------------------
Provides a collection of miscellaneous field formatters for media objects.


Features
--------------------------------------------------------------------------------
The primary features include:

* Field formatter for outputting a media entity's URL.


Requirements
--------------------------------------------------------------------------------
Media module, provided by core.


Notes & Known Issues
--------------------------------------------------------------------------------
It may be necessary to override the appropriate field.html.twig file to remove
erroneous whitespace to make this work properly.

The field formatters might not work correctly if theme debugging is enabled, as
the theme system prepends lots of HTMl comments into the field output.


Credits / contact
--------------------------------------------------------------------------------
Originally written and maintained by Damien McKenna [1].

Ongoing development is sponsored by Mediacurrent [2].

The best way to contact the authors is to submit an issue, be it a support
request, a feature request or a bug report, in the project issue queue:
  https://www.drupal.org/project/issues/media_field_formatters


References
--------------------------------------------------------------------------------
1: https://www.drupal.org/u/damienmckenna
2: https://www.mediacurrent.com/
