INTRODUCTION
============
This Module prevents field machine name from ending (_) underscore. For example, 
when you have brackets at the end of a label, e.g., "Address (2)", 
you end up with a trailing "_" character — field_address_2_.

Perhaps, you simply want to validate field the machine name, especially when it 
is ending with underscore or having two/more undersocre and it has been
truncated

INSTALLATION
============
Refer: 
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules

REQUIREMENTS
============
No Requirements.

CONFIGURATION
=============
Configuration can be done if user has "administer site configuration" permission

Configuration can be access admin/config/underscore_handle/underscoresetting url
 * Check "End Underscore" if want to validate underscore in end
 * Check "Double underscore" if want to validate double underscore in filed
Machine name.

Author/Maintainers
===================
Name: Rajveer gangwar
Email: rajveer.gang@gmail.com
Profile: https://www.drupal.org/u/rajveergang
