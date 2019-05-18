INTRODUCTION
------------

This module provides an integration to Imgix, a real-time image 
processing and image CDN. 

It alters ```theme_image_style``` and alters all images that are going to be rendered to be processed and served by Imgix, increasing substantially the website performance.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/imgix

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/imgix

REQUIREMENTS
------------

This module requires the following external library:

 * php-imgix (https://github.com/imgix/imgix-php)

When you use composer require to install this module then the library should 
be downloaded automatically too.

INSTALLATION
------------

* In your root folder: 

```php
  composer require drupal/imgix
```

* If you don't manage your drupal 8 installation through composer you will 
  need to run the following (in your module folder) after downloading the module:

```php
   composer install
```

CONFIGURATION
-------------

* Configure user permissions in Administration » People » Permissions:

  - Administer imgix

    Users in roles with the "Administer imgix" permission will have access
    the configuration of the module

* Go to admin/config/media/imgix/settings to setup the module according to the options you've setup in the Imgix's dashboard.

MAINTAINERS
-----------

Current maintainers:
 * tregismoreira - https://www.drupal.org/u/tregismoreira
 * Bart Van Thillo (spoit) - https://www.drupal.org/u/spoit

This project has been sponsored by:
 * SOLFISK
 * WIENI
