CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Installation
 * Configuration
 * Developer references

INTRODUCTION
-------------
The Custom View Filters creates two new filters for views filtering:

 * Custom Az Filter
 
 * Node granular date filter

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/documentation/install/modules-themes/modules-8
   for further information.

CONFIGURATION
-------------

On any view, you can add the following filters:

* Custom Az Filter: You can filter by the first letter of first/second word for
a given text field you will have to define. You should provide a machine field
name (title field is unsupported). It can be both an exposed or admin filter.
Use {{ form.custom_view_filters }} to print it directly on a twig template.

* Node granular date filter: You can filter by year or/and month given a date
field. You should provide a machine field name (title field is unsupported).
It can be both an exposed or admin filter. Use {{ form.nodes_granular_dates }}
to print it directly on a twig template.

DEVELOPER REFERENCES
--------------------

 * https://www.webomelette.com/creating-custom-views-filter-drupal-8
 * https://zanzarra.com/blog/custom-views-filter-plugin-drupal-8-bounding-box-geofield
 * https://api.drupal.org/api/drupal/core%21modules%21views%21views.api.php/function/hook_field_views_data/8.6.x
 * https://api.drupal.org/api/drupal/core%21modules%21views%21views.api.php/function/hook_views_data_alter/8.6.x
 * https://drupal.stackexchange.com/questions/260118/how-to-create-exposed-filter-programmatically
