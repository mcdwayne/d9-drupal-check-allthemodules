#Path File Module

The Path File module allows content editors to upload files 
and specify the url at which they will be available. 
Normally, when uploading a file to 
Drupal with the same name as an existing file, 
it is given a new name instead of overwriting the existing file. 
This can problematic when file urls are 
referenced in content, menus, etc but may need to be updated in the future. 
The Path File module allows you to mitigate the 
impact of those changes by setting up a canonical url alias which will 
always point to the most up-to-date file.

##Requirements

There are no contributed modules required for this project, 
but you must ensure the core file module is installed.

##Installation

Simply install the module using drush or UI.  

##Configuration

It will add menu links in the Content administrator menu 
for viewing the list of Path Files and
 a sub-menu item for settings.

Visit the pages at `/admin/structure/path_file_entity` for the list and 
`/admin/structure/path_file_entity/settings` for configuration.

Provides full suite of permissions, 
and allows Anonymous and Authenticated users 
to view published Path Files by default.  
