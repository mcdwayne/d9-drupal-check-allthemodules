
Contents of this file
---------------------

 * Install
 * Config
 * Use
 * Known Issues/Limitations
 * Todo



Install
-----------------

Basic drupal module install/enable the module.


Config
---------

- Config URL: /admin/structure/cyr2lat/settings
- Select every content type you want to transliterate also don't forget to select fields from Content Type you want to affect.
- If you have paragraphs in content type, you only need to select paragraph type.


 
Use
------------

1. Enable module

2. Configure Content/Paragraph Types and fields you want to work with.

3. Run drush command: drush cyr2lat:translate which will create translation nodes for your selected Content Types. You can also use alias for this Drush command (drush c2l-translate)


Notes
-----

- There are a lot of optimisations and fixes that are needed to meke this better.


Known Issues/Limitations
------------------------

- Multilingual sites beside transliteration needs additional config tweek in language settings.



Author/Maintainer
-----------------

- [Strahinja Miljanovic](http://drupal.org/u/sixzeronine)
