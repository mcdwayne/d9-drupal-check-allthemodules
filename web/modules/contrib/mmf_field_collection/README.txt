CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration


INTRODUCTION
------------

The 'Minimum Multiple Fields(MMF): Field Collection' module allows
the administrator to specify a minimum number of Field Collection fields
to appear in the content add form.

This module specifically works for Field Collection fields
which are configured 'Allowed number of values: UNLIMITED'.

Agenda is to initially show
a specific(say, 3) number of fields at content add form,
against the default behaviour that shows only one field.

 
 REQUIREMENTS
------------

This module requires the following modules:

 * Field collection (https://www.drupal.org/project/field_collection)
 
 
INSTALLATION
------------
 
Install as you would normally install a contributed Drupal module. 
See: https://www.drupal.org/documentation/install/modules-themes/modules-8


 CONFIGURATION
-------------

Field Collection fields attached to a content type can be configured.

Make sure while adding Field Collection field to content type, 
the field is/was configured to 'Allowed number of values: UNLIMITED'.

Pictorial view of module's configuration
can be found as image(s) at module page in drupal.org.

 * Attach a Field Collection field to content type
 * While configuring above field, opt 'Allowed number of values: UNLIMITED'
 * Go to Manage Form Display page of the content type
 * Against the field choose widget 'Embedded MMF'
 * Againt the same field, click on gear icon and set 'Minimum Fields' value
 * Check the result by creating a content of this content type
