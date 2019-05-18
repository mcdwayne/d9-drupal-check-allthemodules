# INTRODUCTION
The first Drupal 8 attendance solution, if you need an attendance feature
for your events, courses or classes. this module give you the ability to
add attendance by depending on Flag module.

This module will give you a new field type 'field_attendance',
you can configure this field to work with associated flag to an entity.

# REQUIREMENTS
Flag: https://www.drupal.org/project/flag

# INSTALLATION
We require installation using composer
composer require drupal/flag_attendance_field

OR

1. Download the module to your DRUPAL_ROOT/modules directory, or where ever you
install contrib modules on your site.
2. Go to Admin > Extend and enable the module.

# CONFIGURATION
 1. Install flag module, and set a flag for an entity.
 2. Go to entity manage fields and add a new attendance field.
 3. In field settings page, there is a associated flag field, select the flag.
 4. Now you can edit any node and add dates and attendance for flagged users.
 5. Done.

 ## Contact details
 Anas Mawlawi
 anas.mawlawi89@gmail.com
