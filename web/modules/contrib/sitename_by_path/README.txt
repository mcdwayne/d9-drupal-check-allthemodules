-- SUMMARY --

The Sitename By Path module changes the sitename and frontpage url based on
path. On HOOK_init(), the page's url is compared to Sitename By Path entries
stored in the database. If a match is found then the Drupal global
variables site_name and site_frontpage are are updated then reverted
back on HOOK_exit();

Navigate to: admin/config/search/sitename-by-path
Users with permission "access administration pages" can add/delete/edit entries.

For a full description of the module, visit the project page:
  http://drupal.org/project/sitename_by_path


-- REQUIREMENTS --

None.


-- INSTALLATION --

* Install as usual.


-- CONFIGURATION --

* Configure in Administration » Configuration » Search and metadata:

  - Configuration page: admin/config/search/sitename_by_path

    Add Sitename By Path Entry
     - Simple click "Add Item" button
     - "*" is allowed in path

    Edit/Delete Sitename By Path Entry
     - Edit/Delete link is provided next to each entry

  - Access Sitename By Path configuration page

    Users in roles with the "access administration pages" can 
    access configuration page


-- CUSTOMIZATION --

NA

-- TROUBLESHOOTING --

NA


-- FAQ --

NA


-- CONTACT --

Current maintainers:
* Michael Merrill - http://drupal.org/user/3431897/
