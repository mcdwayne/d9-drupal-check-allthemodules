CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


Introduction
------------


Content Profile Export module 
----------------------------

Content Profile Export module serves two purposes:---

1. Creating the new fields in the Profile Entity Type Bundle from the Node Entity Type Bundle.
2. Copying/cloning the fields from one Node Entity Type to another Node Entity Type. 


REQUIREMENTS
------------

It requires Profile Module to create new Fields in the Profile Entity Type.


INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/node/895232 for further information.



CONFIGURATION
-------------

1. For creating the fields in the Profile Entity Type Bundle :--

a.) Go to "admin/config/content_profile_export" . Fill up the names of the source Node Type Bundle and Destination Profile Type.
b.) Then use the following drush command on the command line:--
    
     drush contentprofilefields

    It will create the fields in the Profile Entity Type and attach field  instances to the Profile Entity Type Bundle same as that of Node Type Bundle.


2.  For creating the fields in the Node Type Bundle from another Node Type Bundle :--

a.) Go to "admin/config/content_fields_to_another". Fill up the names of the source Node Type Bundle and destination Node Type Bundle.
b.) Then use the following  drush command on the command line:-
     
     drush fieldSourceToDestination

    It will attach the field instances to the destination Node Type Bundle.


MAINTAINERS
-----------

Current maintainers:
 * Ankit Bhatia-  https://www.drupal.org/u/ankitbhatianithgmailcom

