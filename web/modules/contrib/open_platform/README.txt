-- SUMMARY --

The Guardian Open Platform module makes it possible to select and collect news content from 
The Guardian newspaper for reuse on your Drupal site via the Open Platform API.

For a full description of the module, visit the project page:
   [http://........]
To submit bug reports and feature suggestions, or to track changes:
   [http://....]



-- REQUIREMENTS --

In its simplest form this module will allow you to display a block based on your search query 
and select the fields you want to display in that block.

To publish content on the site you will need an API key, which you can obtain 
from here http://guardian.mashery.com/.

Once you have an API key go to admin/config/services/open-platform and add your 
API key to the field labelled API KEY.
You can now go to admin/config/services/open-platform and publish content 
returned from your search query in your site.



-- INSTALLATION --

  * Install as usual, see https://drupal.org/node/1294804 for further information.



-- CUSTOMIZATION --

Make a copy of the tpl file in templates/open_platform_block.tpl.php into your 
own local theme and you can customise this file to your liking.
NOTE: Please ensure you always display the powered by Guardian logo. 
See terms and conditions section for more info.



-- TROUBLESHOOTING --

  * If you are having any problems go to admin/reports/status and check the 
    relevant Open Platform status messages. Also makes sure that your server supports cURL.



-- TERMS & CONDITIONS --

This module aims to follow the Terms and Conditions of usage. Check out the 
following link for more information http://www.theguardian.com/open-platform/terms-and-conditions. 
Here is a list of the following thing you can do to make sure you meet conditions required for use.
   * Make sure Cron runs at least once a day on your website. For information on 
     setting up cron visit https://drupal.org/cron 
   * Do not edit any part of the articles you are displaying on our site.
   * When doing any kind of customisation make sure you display the powered 
     by guardian logo


-- CONTACT --

Current maintainers:
   * Temi Jegede(t14) - https://drupal.org/user/495716/
