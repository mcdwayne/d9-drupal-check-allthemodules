Metatag User Role
-----------------
This module allows you to configure meta tags for user pages by user roles
(except for authenticated and anonymous roles). In the case of meta tags for
multiple roles, the user's priority role is used (by default, this is the role
with less weight on the page admin/people/roles). The priority role can be
changed using the special hook.

Requirements
--------------------------------------------------------------------------------
Metatag User Role requires the following:

* Metatag - https://www.drupal.org/project/metatag
* User

How to
--------------------------------------------------------------------------------
1. Install the module.
2. Open admin/config/search/metatag.
3. Click "Add default meta tags".
4. Select "User (%role)" in Type element (%role equal to the label of the role
   for which you want to configure meta tags).
5. Configure your meta tags as usual.

Credits / contact
--------------------------------------------------------------------------------
Developed and maintained by Andrey Tymchuk (WalkingDexter)
https://www.drupal.org/u/walkingdexter

Ongoing development is sponsored by Drupal Coder.
https://www.drupal.org/drupal-coder-initlab-llc
