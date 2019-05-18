INTRODUCTION
------------

"Content Guide" gives you the possibility to provide an extended help for your
editors on a per-field base.

The module allows you to attach documentation written in
"GitHub Flavored Markdown" (https://github.github.com/gfm/) to fields in your
entity forms.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/cg

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/cg


REQUIREMENTS
------------

 * Parsedown (http://parsedown.org) is required to transform the documents from
   "GitHub Flavored Markdown" to proper HTML markup.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/node/895232 for further information.

   It is highly recommended to install "Content Guide" using Composer so its
   dependencies can be installed automatically. Otherwise you will have to
   install Parsedown first manually.


CONFIGURATION
-------------

Go to admin/config/content/cg and enter the path to your documents. This can
either be a full system path or a path relative to your Drupal installation.

To attach a documentation file, go to "Manage form display" of you fieldable
entity type (i.e. admin/structure/types/manage/article/form-display) and open
the fields configuration (by clicking on the gear icon). Now you are able to
enter the path to the document describing the field.
Choosing "Tooltip" as display type would add a small question mark next to the
fields label (clicking on it will open the documents content) and
"Field description" will add the document as the fields description.


TRANSLATION
-----------

To add translated versions of your documents, simply duplicate the document and
append the corresponding language code to the documents name.

Example:

  - article/title.md (will be displayed as default)
  - article/title.de.md (will be used if german is the current interface
    language)
  - article/title.es.md (will be used if spanish is the current interface
    language)


MAINTAINERS
-----------

Current maintainers:
 * Stefan Borchert (stborchert) - https://www.drupal.org/u/stborchert

This project has been sponsored by:
* undpaul
  Drupal experts providing professional Drupal development services.
  Visit https://www.undpaul.de for more information.
