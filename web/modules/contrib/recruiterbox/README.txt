CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers
 * Mentor(s)
 * Sponsor

INTRODUCTION
------------
This module allows your application to interact with recruiter box API. Site Admin
can configure this module to create candidates for multiple openings directly at 
recruiterbox.com.


RECOMMENDED MODULES
-------------------


INSTALLATION
------------
Please follow the general instrcution to installation of modules in Drupal 8.
Ref - https://www.drupal.org/docs/8/extending-drupal-8/installing-modules

CONFIGURATION
-------------
 Generating an API Key: Follow following URL
   https://developers.recruiterbox.com/reference#generating-an-api-key

 Recruiter box API key:
   Add it at admin/config/recruiterbox/recruiterboxsettings

 Recruiter box Form settings
   Add Recruiter Box Opening ID, It should be an Integer value, check following URL.
    https://{recruiterbox_client_name}.recruiterbox.com/app/#openings/dashboard/
   
   Add Drupal Form's ID, the drupal's form ID like node_article_form.

   Add Initial fields mapping, Recruiter box opening has following Initial fields
    First Name, Last Name, Email, Phone, Resume.
    Here we map Recruiter box Initial fields machine name with Drupal Form's fields machine name, 
    Example: field_first_name is Drupal Form's field and first_name is Recruiter box field.
        field_first_name|first_name
        field_last_name|last_name
        field_phone|phone
        field_email|email
        field_resume|resume
        body|description

   Add Profile fields mapping, Admin can add extra fields for an Recruiter box opening at
    https://{recruiterbox_client_name}.recruiterbox.com/app/#openings/{opening_id}/application-form/
    Here we map Recruiter box profile fields machine name with Drupal Form's field machine name, 
    Example:field_dob_date is Drupal Form's field and dob is Recruiter box field.
        field_dob_date|dob
        field_willing_to_relocate|willing_to_relocate


MAINTAINERS
-----------

Current maintainers:
 * Sammit Kulve (samit.310) - https://www.drupal.org/u/samit310
 * Kapil Kataria(kapil17) - https://www.drupal.org/u/kapil17

Mentor(s):
 * Ravindra Singh (RavindraSingh) - https://www.drupal.org/u/ravindrasingh

Sponsored By:
 * Srijan Technologies, INDIA - https://www.drupal.org/srijan-technologies-india


