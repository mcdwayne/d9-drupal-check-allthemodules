BxSlider module integrates the bxSlider library (bxslider.com) with Fields.

Why bxSlider?

    Fully responsive - will adapt to any device
    Horizontal, vertical, and fade modes
    Slides can contain images, video, or HTML content
    Advanced touch / swipe support built-in
    Uses CSS transitions for slide animation (native hardware acceleration!)
    Full callback API and public methods
    Small file size, fully themed, simple to implement
    Browser support: Firefox, Chrome, Safari, iOS, Android, IE7+
    Tons of configuration options


DEPENDENCIES

 BxSlider Library 
 - https://github.com/stevenwanderski/bxslider-4/archive/v4.2.15.zip

INSTALLATION

 Libraries can be installed: manually, with Drush or with Composer.  
 
 
MANUAL INSTALLATION 
 
 1. Download the libraries 
    https://github.com/stevenwanderski/bxslider-4/archive/v4.2.15.zip

    Unzip and put the content of the archive to the /libraries/bxslider
    (create required directories).

    Please note, the file jquery.bxslider.min.js must be accessible 
    in path /libraries/bxslider/dist/jquery.bxslider.min.js.

 2. Enable the module.

 3. Select some content type, then select 'Manage display' and select a
    formatter "BxSlider" for required images field. Then click to the 
    formatter settings for changing BxSlider's settings

    Select a formatter "BxSlider - Thumbnail slider", if is needed to use
    a carouser thumbnail pager.

    For example, go to /admin/structure/types/manage/article/display , 
    select a formatter BxSlider for an Images field and click 'the gear'
    at the right side.
    
    
INSTALLATION WITH COMPOSER    
    
 1) Install the required library with Composer. According 
    https://www.drupal.org/project/drupal/issues/2873160 , it is little
    complicated for now:
    
    1.1 Please, add appropriate fragments (between dot lines) to 
    appropriate section of composer.json in the root of the site.  
 
 "repositories": [
 ....... Add it to "repositories" section ....................................
 ..... Note: add "," after the previous item, so it should look like "}," ....
 ............................................................................. 
 
{
 "type": "package",
 "package": {
   "name": "stevenwanderski/bxslider",
   "version": "4.2.15",
   "type": "drupal-library",
   "dist": {
     "url": "https://github.com/stevenwanderski/bxslider-4/archive/v4.2.15.zip",
     "type": "zip"
   },
   "require": {
     "composer/installers": "^1.2"
   }
 }
}
   
   .............................................................................
 ],
 "require": {
   ....... add it to "require" section ......................................... 
   ... Note: add "," after the previous item, so it should look like " ", " ....
   .............................................................................
   
   "stevenwanderski/bxslider": "~4.2.15"
   
   .............................................................................
 }
   
   1.2 Run "$ composer update" from the root directory of the site.
   
   
INSTALLATION WITH DRUSH   

 Please, execute the command "drush bxslider-plugin".


MORE

 For development of a carousel thumbnail pager was used
 http://stackoverflow.com/questions/19326160
 /bxslider-how-is-it-possible-to-make-thumbnails-like-a-carousel
