INTRODUCTION
------------

Dark Mode is module for drupal that allows drupal administrator to set two 
themes for any drupal installation to switch between, depending on the schedule.

By using dark mode module you can enable dark theme for your drupal 
installation. 

Dark mode is a design trend. Many reading applications 
(Medium App, Twitter etc.) have it already. It is not only about just inverting 
all colors, but it's also about art direction.


REQUIREMENTS
------------

This module requires the following modules:
 * Time Field (https://www.drupal.org/project/time_field)

INSTALLATION
------------

 * Install as you would normally install a contributed Drupal module. Visit:
   https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
   for further information.

CONFIGURATION
-------------
 
   - Access administration menu

     User roles with the "Access administration menu" permission will be
     able to configure dark mode theme.

FAQ
---

Q: When should I use this module?
A:  If you want to switch between two themes, based on schedule set then you 
    should use this module. Default theme will be applicable as usual. 
    But dark theme will be activated/de-activated on given time.

Q: Why should I need this module?
A: To enable dark theme functionality you should use this module.

Q: How can i achieve dark theme functionality?
A: Create a new dark theme for your website than enable this theme from module
    Configuration.

Q: Does This module require cron?
A:  No this module don't require cron.

Q: Where is the default theme configuration?
A:  Default theme is configured from admin/appearance menu.

Q: When will the dark mode theme be activated?
A:  Dark theme will be activated automatically at the time set in scheduler at
    admin/config/user-interface/dark_mode/adminsettingpage menu.

Q: Why dark mode is better?
A: People with astigmatism (approximately 50% of the population) find it harder 
    to read white text on black than black text on white. Part of this has to do
    with light levels: with a bright display (white background) the iris closes
    a bit more, decreasing the effect of the "deformed" lens; with a dark 
    display (black background) the iris opens to receive more light and the 
    deformation of the lens creates a much fuzzier focus at the eye.
    More: https://goo.gl/BMjciv (https://ux.stackexchange.com)

MAINTAINERS
-----------

Current maintainers:
Name: Rajveer Gangwar 
Email: rajveer.gang@gmail.com
Profile url:  https://www.drupal.org/u/rajveergang
