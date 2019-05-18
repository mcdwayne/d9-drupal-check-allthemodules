# CONTENTS OF THIS FILE
-----------------------------------------------------------------------

* OVERVIEW
* USAGE
* REQUIREMENTS
* INSTALLATION

# OVERVIEW
-----------------------------------------------------------------------
The Image Field Permissions allows you to set pseudofield-level permissions
for the image fields. Permissions can be set for creating, editing and
viewing the image file, title and alt attributes depending on the users
roles. As example you can allow users to edit (and translate) the title and
alt attribute without allowing them to change the image file.

# USAGE
-----------------------------------------------------------------------

The Image Field Permissions module works just as Field Permissions module, but
provides extra permission settings for the image fields. Once Image Field
Permissions module is installed you can assign the following permissions to
any role on your site:

  * Upload own image file
  * Edit own image file
  * Edit anyone's own image file
  * View own image file
  * View anyone's image file
  * Edit own image alt value
  * Edit image alt value
  * Edit own image title value
  * Edit image title value

These permissions will also be available on the standard permissions page at
Administer -> People -> Permissions.

# REQUIREMENTS
-----------------------------------------------------------------------
This module requires the following modules:
  * Field Permissions (https://www.drupal.org/project/field_permissions)

# INSTALLATION
-----------------------------------------------------------------------

1) Copy all contents of this package to your modules directory preserving
   subdirectory structure.

2) Go to Administer -> Modules to install module. If the (Drupal core) Field UI
   module and Field Permissions module are not enabled, do so.

3) Review the settings of your fields. You will find a new option labelled
   "Field visibility and permissions" that allows you to control access to the
   field.

4) If you chose the setting labelled "Custom permissions", you will be able to
   set this field's permissions for any role on your site directly from the
   field edit form, or later on by going to the Administer -> People ->
   Permissions page.

5) Get an overview of the Field Permissions at:
   Administer -> Reports -> Field list -> Permissions

# AUTHOR/MAINTAINER/CREDITS
-----------------------------------------------------------------------
image_field_permissions 8.x-1.x
  - Developed by vladdancer (https://www.drupal.org/u/vladdancer)
  - Design by matsbla (https://www.drupal.org/u/matsbla)
  - Sponsored by Globalbility (https://www.drupal.org/globalbility)
