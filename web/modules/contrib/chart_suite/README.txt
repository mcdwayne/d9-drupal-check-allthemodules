                         Chart Suite module for Drupal 8

                      by the San Diego Supercomputer Center
                   at the University of California at San Diego


CONTENTS OF THIS FILE
---------------------
 * Introduction
 * Requirements
 * Installation
 * Configuration for entity view pages
 * Configuration for views
 * Troubleshooting
 * Maintainers


INTRODUCTION
------------
The Chart Suite module for Drupal 8 provides a file field formatter that uses
the separate Structured Data API to parse a variety of textual table, graph,
and tree file formats. The field formatter then plots their data using
Google Charts.


REQUIREMENTS
------------
The Chart Suite module requires Drupal 8.x and PHP 7.x.

The module includes a copy of the Structured Data API from the San Diego
Supercomputer Center (SDSC). The library is in the "libraries" folder.

The module uses the free Google Charts service, which is accessed by
including a Javascript library served by Google. That library loads further
libraries on demand from Google. Due to the way Google Charts is structured
and its terms of service, its Javascript libraries cannot be served locally
so those files cannot be included in this module.

The module does not require any third-party contributed modules or libraries.
The module only requires modules included in Drupal core and already enabled by
most web sites.


INSTALLATION
------------
Install the module as you would normally install a contributed Drupal module.
Visit:
  https://www.drupal.org/docs/user_guide/en/config-install.html


CONFIGURATION FOR ENTITY VIEW PAGES
-----------------------------------
To configure field formatters for an entity view page, you must have the
Drupal core Field UI module enabled. Field UI provides a "Manage display"
page for each entity type. From this page you can select the field formatter
to use for each field, then click on the formatter's gear icon to the right
of the field row. This presents the formatter's configuration page. Click
"Update" to save that configuration, and "Save" to save the display.

Thereafter, each time the field is shown on an entity view page, Drupal will
invoke the chosen field formatter to present the value.

CONFIGURATION FOR VIEWS
-----------------------
To configure field formatters for a view, you must have the Drupal core
View UI module enabled. View UI provides a page for each view from which
you can select the fields to include in a table or list of entities. For
each field, you can configure how the field is presented by selecting a
field formatter and adjusting that formatter's settings.

Thereafter, each time the field is shown for an entity in a row of a view,
Drupal will invoke the chosen field formatter to present the value.


TROUBLESHOOTING
---------------
The module does not have a database schema and it has no configuration
settings of its own. There is nothing to change or reset if there is trouble.


MAINTAINERS
-----------
Current maintainers:
 * David Nadeau
   San Diego Supercomputer Center
   University of California at San Diego

This project has been sponsored by:
 * The National Science Foundation. See NSF.txt.
