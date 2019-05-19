# About Slick Lightbox

Provides a simple integration with Slick Lightbox.

## REQUIREMENTS
* Slick Lightbox library:
  + Download Slick Lightbox archive from
    [Slick Lightbox](https://github.com/mreq/slick-lightbox)
  + Extract it as is, rename **slick-lightbox-master** to **slick-lightbox"**,
    so the asset is available at:

    **/libraries/slick-lightbox/dist/slick-lightbox.min.js**
    **/libraries/slick-lightbox/dist/slick-lightbox.css**

    You can remove non-essential files, except these two files.

* [Slick](http://dgo.to/slick)


## INSTALLATION
Install the module as usual, more info can be found on:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules


## USAGE / CONFIGURATION
Enable this module and its dependency, core image and Blazy modules.

### FIELD FORMATTERS
* Visit **/admin/config/people/accounts/fields**, or **/admin/structure/types**,
  or any fieldable entity, click **Manage display**.
* Under **Format**, choose blazy-related formatters:
  **Blazy**, **Slick carousel**, etc. for image field.
* Click the **Configure** icon.
* Under **Media switcher**, choose **Image to Slick Lightbox**. Adjust the rest.

### BLAZY FILTER
* Visit **/admin/config/content/formats/full_html**, etc.
* Enable **Blazy Filter**.
* Under **Media switcher**, choose **Image to Slick Lightbox**.


## FEATURES
* Has no formatter, instead integrated into **Media switcher** option as seen at
  Blazy/ Slick formatters, including Blazy Views fields for File Entity and
  Media, and also Blazy Filter for inline images.
* Swipeable video for core Media module.

## SLICK LIGHTBOX OPTIONSET
We only have one optionset for the Slick Lightbox, override it accordingly:
**/admin/config/media/slick/list/slick_lightbox/edit**


## LIMITATION
Has no skins, yet. Patches or contributions are very much welcome to improve the
first looks like what Colorbox has OOTB.

Good at CSS? We welcome your contributions to make this Slick Lightbox slick,
even if you don't patch, contributing just a CSS attachment is very much
welcome. We can provide UI options later once we have some skins.


## SIMILAR MODULES
[Colorbox](http://dgo.to/colorbox)


## AUTHOR/MAINTAINER/CREDITS
gausarts

[Contributors](https://www.drupal.org/node/2547553/committers)

## READ MORE
See the project page on drupal.org:

[Slick Lightbox module](http://drupal.org/project/slick_lightbox)

See the Slick Lightbox docs at:

* [Slick Lightbox at Github](https://github.com/mreq/slick-lightbox)
* [Slick Lightbox website](http://mreq.github.io/slick-lightbox/)
