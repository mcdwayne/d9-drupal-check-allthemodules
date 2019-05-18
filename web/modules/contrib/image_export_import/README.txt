CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended modules
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

In the help of this module, user can create/update node for selected image field
(single value field as well as multi value) with ALT and Title. User can also 
create new node (with Title, Body fields) and attach images with new nodes.

User can also export nodes for selected image field in csv file with respected 
columns (nid, node_title, image_name (Pipe separated incase of multiple), 
IMG_alt, IMG_title, summary, body).

Body field is optional in csv.If user will check "Body and Summary" 
option on form then body will export in csv.

User can also delete unused files from CMS. after installation goes to
"/admin/content/unused-files" page, and see all unused files in cms.


REQUIREMENTS
------------

No special requirements.


RECOMMENDED MODULES
-------------------

 * Image and view module in core.

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module.
   See: https://www.drupal.org/node/895232 for further information.
   
 * Clear cache after module installation. After that user will see 
   menu tabs (Manage image files , Unused files) in admin (/admin/content).


CONFIGURATION
-------------
  
  How to Use: 
  --------------
  User need to execute export operation first, after that you got
  csv file which have following columns (Nid, Node_Title, Image_file_name (Pipe
  separated in case of multi value)). and all images backuped under 
  "sites/default/files/migrate_images" directory automatically.

  Then you need to update existing csv file with image_filename, image_alt, 
  image_title in csv file, and execute import operation with updated file. 
  Please upload images zip file for new images.
  
  Admin can truncate/delete "sites/default/files/migrate_images" directory 
  manually after export/import/delete operation for reduce server space.

  FOR EXPORT
  -----------
  Using export operation user will got a csv file which have following columns 
  (Nid, Node_Title, Image_file_name (Pipe separated in case of multi value)). 
  Images will automatically exported under "sites/default/files/migrate_images" 
  directory. 
  
  If user will check "Body and Summary" option on form then body will export in
  csv.
  -------------------------------------------------------------------------
  
  For IMPORT
  ----------
  
  CSV columns order should be as per exported csv (got from export operation), 
  Please do not change CSV file column order, Please use exported CSV file 
  and update content in this file to Export operation.
  
  Update existing node: 
  ---------------------
  User can update exiting image, just update image name, Alt, title (pipe(|)
  separated in case of multi value).
  
  Create new node :
  ----------------
  User can create new node and attached images with node, for that just use
  exported CSV and add new row in csv and leave empty NID column for new node.
  Just check "Create new node" option during image import, it's required
  for new node creation.
  
  Body and Summary
  ----------------
  User can update body and summary for new node as well as existing node,
  For that need to check "Body and Summary" option during import.
  
  Note: (1) Select multiple images files and compress directly, means do not 
  compress image folder.Otherwise node will not create/update.
  
  (2) If your CSV file have new row (empty NID column), Then use one time
  import operation with this CSV file, Otherwise due to empty NID column 
  duplicate node will create in CMS.
  
  (3) Not required: Backup your Database before executing Import operation. 
  Using Backup and Migrate (https://www.drupal.org/project/backup_migrate).
  ----------------------------------------------------------------------
  
  FOR DELETE UNUSED FILES:
  ------------------------
  Open "/admin/content/unused-files" page to check unused files in CMS.
  If you want to delete these files then execute "Delete unused files"
  Operation.  

MAINTAINERS
-----------

Current maintainers:
 * Raushan Tiwari - https://www.drupal.org/u/raushan
