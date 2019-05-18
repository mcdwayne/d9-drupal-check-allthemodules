This module allows quick edit to be disabled on certain fields.

INSTALLATION
--------------------------------------------------------------------------
1. Copy the restricted_quickedit.module to your modules directory
2. Enable module.     
3. Visit the field display page and select to disable quickedit.
4. For base fields quick edit can be disabled by setting the setting 
disable_quick_edit to TRUE via an update hook or 
hook_entity_base_field_info_alter.
