*********************************************************
-----------------------S3 Zip Image Upload---------------
*********************************************************

Introduction
------------
S3 Image Upload module allows to upload a zip file of images on Amazon S3.
These zip files get extracted to a specified folder on S3.
The zip folder will contains images directly.
zip folder creation :
Select all images->right clicked->compressed zip folder.


Requirements
------------
1. amazon-s3-php-class library :- You will need to
download amazon-s3-php-class library from
https://github.com/tpyo/amazon-s3-php-class and extract the files to
"/sites/all/libraries/" directory(e.g:/sites/all/libraries/amazon-s3-php-class/)
you can directly do a "git clone
https://github.com/tpyo/amazon-s3-php-class.git" in
"/sites/all/libraries/".     or     You can download this module using
drush command "drush dl s3_zip_image_upload" after that do
"drush en s3_zip_image_upload" it will
download the amazon-s3-php-class library as well automatically.
2. awssdk2 library :- You will need to
download awssdk2 library from https://www.drupal.org/project/awssdk2 and
extract the files to the "/sites/all/libraries/" directory.
3. s3 file system :-  You will need to
download s3fs module from https://www.drupal.org/project/s3fs and
extract the files to the "/modules/contrib/" directory.
Or you can download this module using
drush command "drush dl s3fs" after that do "drush en s3fs" it will
download the awssdk2 library as well automatically.


Installation & Use
-------------------
* Install as you would normally install a contributed Drupal module. See:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.
* 1. Download project from https://www.drupal.org/s3_zip_image_upload and unzip
     the project.
* 2. Place the project under '/modules/contrib' directory.
* 3. Install it from module installation page.

Configuration
-------------
 * Configure user permissions in Administration » People » Permissions:
 * Find permission "Access Image Upload."
 * This is permission for access the menu link used to "Upload Image".
 * Access link Administration » management » image_upload.
  1. Enter S3 folder name where images will get extracted.
  2. Upload zip file which contains images directly.
  3. Submit form.
  
Limitation
------------
None 

Features
--------
* Uploads Images on Amazon S3.

Current maintainers:
---------
* Neha Patil (neha_patil) - https://www.drupal.org/u/neha_patil
* Anurag Shah (anurags) - https://www.drupal.org/u/anurags
* Yash Khandelwal (yash_khandelwal) - https://www.drupal.org/u/yash_khandelwal
