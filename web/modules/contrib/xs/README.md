XPath scraper
===============

XPath scraper solution for Drupal 8

How does it work
================

Enable XPath scraper first.
Create new module that requires XPath scraper and put the configuration at {module_name}/src/Controller/ScraperConfigController.php.

For example kindly see xpath_scraper_test which has the following files:

* modules/xpath_scraper_test/xpath_scraper_test.info.yml
* modules/xpath_scraper_test/xpath_scraper_test.services.yml
* modules/xpath_scraper_test/src/Controller/ScraperConfigController.php