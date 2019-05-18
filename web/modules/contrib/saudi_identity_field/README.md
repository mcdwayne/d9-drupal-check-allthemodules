D8 port:

Not stable and needs enhancements the main issue here
http://drupal.stackexchange.com/questions/211784/send-argument-to-custom-field-constraint


CONTENTS OF THIS FILE
---------------------
   
 * SUMMARY
 * Installation
 * Configuration
 * Maintainers

-- SUMMARY --

This module provides a validation field for Saudi national number 
Saudi or none-Saudi(Iqama) ,, algorithm validation was designed by 
Eng.Abdul-Aziz Al-Oraij @top7up 
which can be used with any entities(Node, User, etc).

REQUIREMENTS
------------

No special requirements.


-- INSTALLATION --

Put module in modules folder and enable from admin/extend or by: drush en saudi_identity_field -y


-- CONFIGURATION --

 - Go to (Home » Administration » Structure) then 'manage fields' 
 choose your content type add a field of type 'Saudi identity'
 then just complete its configuration ,, this can go for any entity.

 - You can limit valdation of the field for Saudis ID, residents
  or both of them in every field you create by the options under
  'Saudi/Iqama Validator' in field settings.   

-- Maintainers --
Current maintainers:
* Essam AlQaie (3ssom) - https://www.drupal.org/u/3ssom
* Abdullah Bamelhes (drpl) - https://www.drupal.org/u/drpl
