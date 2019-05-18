CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------

This module provides an image lazyloader.
Images will only show a small thumbnail first for fast page loading.
When an image is scrolled into view, the original image is loaded and shown.

Loaded images also get a 'loaded' class when done.


REQUIREMENTS
------------

This module has no other requirements outside of Drupal core.


INSTALLATION
------------

Install the lazyload_images module as you would normally install a
contributed Drupal module:
- require the module:
```
composer require drupal/lazyload_images --prefer-dist
```
- enable the module:
```
drush en lazyload_images -y
```

- This module provides a library that you need to include on the
twig files where you want to use it:
```
{{ attach_library('lazyload_images/lazyloader') }}
```

- Finally, add the class 'js-lazyload-image' to your image.
Remove the 'src' attribute and set the 'data-src' attribute to the
desired image that should be lazyloaded:
```
<img data-src="mylargeimage.jpg" class="js-lazyload-image">
```
Or point the 'src' attribute to a very small thumbnail of the actual image.:
```
<img src="verysmallthumbnail.jpg" data-src="mylargeimage.jpg" class="js-lazyload-image">
```
Thubnails will automatically be blurred and have an unblur effect when
the image is lazy loaded.

Picture elements rendered with drupal_image in twig can now also lazy load:
```
{{ drupal_image(node.myimagefield.0.entity.uri.value, 'large', {
    alt: node.myimagefield.0.alt,
    title: node.myimagefield.0.title,
    class: 'js-lazyload-image',
}, true) }}
```

CONFIGURATION
--------------

There is no configuration needed for this module.


MAINTAINERS
-----------

The 8.x.1.x branch was created by:

 * Joery Lemmens (flyke) - https://www.drupal.org/u/flyke