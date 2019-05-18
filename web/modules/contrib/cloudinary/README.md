The module provides a library and stream wrapper of Cloudinary service.

It can help user easily to use Cloudinary image transformation in Drupal,
 and it can convert all drupal default image effects
 into Cloudinary transformations.

Modules
=======

This module include several sub-modules, some modules can be
 used alone as other modules.

### cloudinary

Provides image transformations of Cloudinary,
 implements hook_image_effect_info().

Provides image style feature with Cloudinary image transformations.

Auto convert image effects (Crop, Desaturate, Resize, Rotate, Scale,
 Scale and crop) of drupal's image style into Cloudinary image transformations,
 without patch.

### cloudinary_streamwrapper

Provides stream wrappers to access Cloudinary files (images, raw files).
 Support read and write with "cloudinary[.folder]".

So you can easily upload your images to Cloudinary with default drupal method
 based on streamwrapper "cloudinary://".

And this module has a hook let user can easily convert other image effects
 to Cloudinary transformation, cloudinary module has been use this hook to
 implemnt Cloudinary transformation for drupal default image effects.

### cloudinary_storage

Provides storage file structure of Cloudinary to local,
 it will be load file structure from storage to reduce network requests
 and improve loading speed for uploaded Cloudinary files.

Also it has several sub-modules to implement storage based on
 db, filesystem, mongodb, redis.

- cloudinary_storeage_db - Save file structure into drupal database.
- cloudinary_storeage_file - Save file structure into filesystem.
- cloudinary_storeage_mongodb - Save file structure into mongodb,
  require Mongodb(https://www.drupal.org/project/mongodb).
- cloudinary_storeage_redis - Save file structure into redis,
  require Redis(https://www.drupal.org/project/redis).

### cloudinary_sdk

Support library of Cloudinary SDK for the other modules.
 Implements hook_libraries_info().


Requirements
============

- Libraries(http://drupal.org/project/libraries)
- Cloudinary SDK for PHP(https://github.com/cloudinary/cloudinary_php)
- Cloudinary API Account(https://cloudinary.com/console)

Usage
=====

### Drush make file

On github repository(git@github.com:everright/cloudinary_drush_make.git),
 it include a drush make file for you to test cloudinary module quickly.

You just need to clone the make files, then drush make it
 and install your drupal site.

### cloudinary_sdk

All modules dependency cloudinary_sdk.

- Log in cloudinary console 'https://cloudinary.com/console' to get API account.
- Install and enable module 'cloudinary_sdk' as usual.
- Go to cloudinary settings page 'admin/config/media/cloudinary'.
- On settings page, type your Cloud name, API key and API secret.
- Click 'Save configuration' button to save API account.
  In order to check the validity of the API, system will be auto
  ping your Cloudinary account after change API settings.
- If there has no error messages, it means connect to
  cloudinary_sdk successfully.

### cloudinary_stream_wrapper

- Install and enable module 'cloudinary_sdk' as usual.
- After module installed, you will be see cloudinary stream wrapper
  option on file or image field setting form.
- You also can use cloudinary stream wrapper "cloudinary://" to save
  images with your code, like
  "file_save_data(your image data here, cloudinary://sample.jpg))".
- In cloudinary settings page, you can enable more stream wrappers for
  Cloudinary with folder name under root folder, after enable it, you can
  use "cloudinary.folder_name://" to save your images into this folder as root.

### cloudinary_storage
- Install and enable module 'cloudinary_storage' and it's sub-modules as usual,
  you can just choose one sub-module to cache the file structure.
- After module installed, in cloudinary settings page,
  you can enable one of cloudinary storage settings.
- If you enabled cloudinary storage for file structure,
  it will be reduce network requests and improve
  loading speed for uploaded Cloudinary files.

### cloudinary
- Install and enable module 'cloudinary' as usual.
- After module installed, it will auto convert image effects
  (Crop, Desaturate, Resize, Rotate, Scale, Scale and crop) of
  drupal's image style into Cloudinary image transformations, without patch.
- Also it will be provides a new image effect "cloudinary_crop" on image style
  manage page, you can easy to create cloudinary transformation directly.

Bugs
====

Please report bugs and issues on drupal.org in the issue queue:
http://drupal.org/project/issues/cloudinary

Remember to first search the issue queue and help others where you can!

Credits
=======

The module was built by everright in Ci&T (http://www.ciandt.com).
