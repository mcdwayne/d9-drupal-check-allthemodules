VIEWS ORDER BY DELTA
----------------

CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Features
 * Implementation
 * Integration (with other modules)
 * Installation and configuration
 * Future developments
 * Contributions are welcome!!
 * Credits / Contact
 * Link references


INTRODUCTION
------------

This module enables the user to proper order items by the delta value of a
referenced content. Views actually has a field to do that, but views does not
have a proper way to use the properties from the field that is used to bridge
the entity that you are displaying and the referenced entity.

For example:

You have taxonomy terms for categories, each category has his own page already
configured where you display some content with some custom fields. Now you want
to display a slideshow at your taxonomy_term page, for this, you have a
content type called "Slides".

To do that, you:
  * Create a field on the taxonomy_term called field_slides that accept multiple
    nodes and is a entity_reference to nodes of type slide.
  * Create a view to list nodes of type slide.
  * Add a relationship using the field_slides from node to taxonomy_term.

And then, the tricky part, you want to order the nodes in the same order they
are referenced in the field_slides at your taxonomy term. You add a sort by
field_slides:delta and use the relatioship field_slides. Because of the way
views works, the results will be duplicated even using pure distinct.

Note, if in this case you use the 2 nodes content types instead of the
taxonomy_term, you will be able to use the field_slides without a relationship
and then your ordering will works. Thats because you have the field_slides on
the entity you are listing too, the SQL will order the content correctly, but
trying to use the field_slides on your slides content type (that probably
doesn't exists), but the SQL will works. If you are doubting, go ahead and do
the taxonomy_term + node example, then add a field_slides to the slides
content type and filter not using any relationship, it will work.


FEATURES
--------

This module enables a new field called "Order by delta (using field_name)" that
is created for every entity_reference field. Use this field for sorting by
delta.


IMPLEMENTATION
--------------

This module simple created a new handler to sort by.


INTEGRATION (WITH OTHER MODULES)
--------------------------------

This module doesn't depends on any other module and doesn't interact with any
other module but views.


INSTALLATION AND CONFIGURATION
------------------------------

1 - Download the module and copy it into your contributed modules folder:
[for example, your_drupal_path/sites/all/modules] and enable it from the modules
administration/management page.

2 - Configuration:
There is nothing to configure, just go to your views and add the sort handler.


FUTURE DEVELOPMENTS
-------------------

In the future, I want to design and implement a better way to work with the
fields that are used as a bridge on relationships, if I get to a good solution,
I would like to have it on Drupal core.


CONTRIBUTIONS ARE WELCOME!!
---------------------------

Feedback, features or comments in general are highly appreciated.


CREDITS / CONTACT
-----------------

Currently maintained by João Sausen (Mete) [1], all initial development,
documentation and testing by João Sausen.

This module was sponsored by:
MMDA [http://www.mmda.com.br/]


LINK REFERENCES
---------------

1: https://www.drupal.org/u/mete
