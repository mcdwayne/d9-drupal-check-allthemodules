
Provides a simple Blazy PhotoSwipe integration.

REQUIREMENTS
[1] https://drupal.org/project/photoswipe
[2] https://drupal.org/project/blazy (post Beta4)


FEATURES
- Has no formatter, instead integrated into "Media switch" option as seen at
  Blazy/ Slick formatters, including Blazy Views fields for File ER and
  Media Entity.
- Supports swipeable Videos if Video Embed Media is installed.


INSTALLATION
Install the module as usual, more info can be found on:
https://drupal.org/documentation/install/modules-themes/modules-8

Enable Blazy PhotoSwipe module under "Blazy" package:
/admin/modules#edit-modules-blazy


USAGES
o Go to any "Manage display" page, e.g.:
  admin/structure/types/manage/page/display

o Find a Blazy/ Slick formatter under "Manage display".
  Or add a new Views field named Blazy for File ER, or Media Entity.

o Choose "Image to photoswipe" under "Media switch" option.
  Adjust anything else accordingly.


RECOMMENDED MODULES
[1] https://drupal.org/project/media_entity
[2] https://drupal.org/project/video_embed_field with Video Embed Media


AUTHOR/MAINTAINER/CREDITS
gausarts

READ MORE
See the project page on drupal.org: https://drupal.org/project/blazy_photoswipe.
