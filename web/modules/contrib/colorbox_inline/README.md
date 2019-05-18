# COLORBOX INLINE

## CONTENTS OF THIS FILE

 * [Introduction](#introduction)
 * [Requirements](#requirements)
 * [Recommended Modules](#recommendedsimilar-modules)
 * [Installation](#installation)
 * [Configuration](#configuration)
 * [Maintainers](#maintainers)

## INTRODUCTION

Colorbox Inline allows the user to open content already on the page within a
colorbox.

## REQUIREMENTS
Requires the following modules:

 * [Colorbox](https://drupal.org/project/colorbox)

## RECOMMENDED/SIMILAR MODULES
 * [colorbox_load](https://www.drupal.org/project/colorbox_load): To load
 content via AJAX.

## INSTALLATION

Install as you would normally install a contributed Drupal module. See also the
[Core Docs](https://www.drupal.org/docs/8/extending-drupal-8/installing-modules)

## CONFIGURATION

To create an element which opens the colorbox on select:

Add the attribute `data-colorbox-inline` to an element and make its value a
selector for the content you wish to open.
Eg, `<a data-colorbox-inline=".user-login">User Login</a>`.
Optional extra configuration you can add:

 * `data-width` and `data-height` to the anchor to control the size of the
  modal window.
 * `data-class` to add a class to the colorbox wrapper.
 * `data-rel="[galleryid]` to add next/previous options to the colorboxes.


## MAINTAINERS

Current maintainers:

 * [Sam Becker (Sam152)](https://www.drupal.org/user/1485048)
 * [Renato Gonçalves (RenatoG)](https://www.drupal.org/user/3326031)
 * [Nick Wilde (NickWilde)](https://www.drupal.org/u/nickwilde)

Supporting maintenance and support provided by:

 * [PreviousNext](https://www.drupal.org/previousnext)
 * [CI&T](https://www.drupal.org/cit)
