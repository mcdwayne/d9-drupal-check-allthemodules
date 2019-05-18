OwlCarousel2
------------

INTRODUCTION
-------------
This module integrates the beautiful carousel slider 
[Owl Carousel 2](https://owlcarousel2.github.io/OwlCarousel2/) through blocks 
integrations.

Effects provided by the [Animate.css](https://daneden.github.io/animate.css/)

INSTALLATION
-------------
To install OwlCarousel 2 on Drupal, proceed as usual and then download and
 copy the `dist` folder of the OwlCarousel2 v2.3.4 javascript library on 
 `\libraries\OwlCarousel2\dist`.

REQUIREMENTS
------------
 [OwlCarousel2](https://github.com/OwlCarousel2/OwlCarousel2/archive/2.3.4.zip)
 library
 
CONFIGURATION
-------------
After installation, a new tab `Carousel` will be present on `Content` admin 
 menu.
 
1. On content menu, click on `Carousel` tab, `Add OwlCarousel2` to create a new
carousel.
2. You can fill the carousel Name and configuration on the form or skip it to do
 it later.
3. Click on `Save`
4. A new fieldset will be presented. Include the carousel items there.
  - Items can be Image, Youtube or Vimeo urls or Views results.
  - For images, it can be a static image or a image linked to a content entity.
  The view mode will define which fields of the content will be presented.
  - For view results, the view result will be considered, not the fields. The 
  content presentation will be based on the view mode selected.
5. After you've included all items, click on save and a preview will be 
 presented.
6. Once you have created your carousel, go to admin/structure/block and place a
new block wherever tou want.
7. On the block pop-up, select `Carousel Block` and in `Carousel` field choose
 the carousel you've created.
8. Finish the block configuration as usual.

PS: You can use Display Suite or Page Manager to configure the OwlCarousel2 
blocks as well.

MAINTAINERS
-----------
Current maintainers:
* Yuri Seki (yuriseki) 
[https://www.drupal.org/user/1523064](https://www.drupal.org/user/1523064)
