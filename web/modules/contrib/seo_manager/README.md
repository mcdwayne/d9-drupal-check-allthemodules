SEO Manager
-------
SEO Manager allows you to enable and configure all SEO settings.

This version should work with all Drupal 8 releases, though it is always
recommended to keep Drupal core installations up to date.


Requirements
--------------------------------------------------------------------------------
SEO Manager for Drupal 8 requires the following:

* Pathauto
  https://www.drupal.org/project/pathauto
* Metatag
  https://www.drupal.org/project/metatag
* Schema.org Metatag
  https://www.drupal.org/project/schema_metatag
* Redirect
  https://www.drupal.org/project/redirect
* Simple XML sitemap
  https://www.drupal.org/project/simple_sitemap
* Yoast SEO
  https://www.drupal.org/project/yoast_seo    
* Google analytics
  https://www.drupal.org/project/google_analytics  


Standard usage scenario
--------------------------------------------------------------------------------
1. Install module by Composer.
2. Enable the module.
3. Open /admin/help/seo_manager and follow the instructions.


Uninstallation
--------------------------------------------------------------------------------
1. Disable module with its dependencies by Drush
   drush seo_manager:uninstall
   Or
   drush smun
2. Remove module from composer and run composer update.
