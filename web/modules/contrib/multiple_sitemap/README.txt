CONTENTS OF THIS FILE
----------------------
  * Introduction
  * Requirements
  * Installation
  * Configuration
  * Maintainers

INTRODUCTION
-------------

"Multiple sitemap" module allows you to create multiple sitemaps for
your project. Sometimes we need to categorize our sitemap into the
different section for better understandings.This module creates one index
xml sitemap file and other as sub-files. We submit only index file to
search engines.The Index file contains sub-files links.

REQUIREMENTS
-------------
  * https://moz.com/blog/multiple-xml-sitemaps-increased-indexation-and-traffic

INSTALLATION
-------------
  * Clone the branch in your module folder
    git clone --branch
    git clone --branch 8.x-1.x Jitujain@git.drupal.org:project/multiple_sitemap.git
  * Go to  module page 'admin/modules'.
  * Enable module.

CONFIGURATION
---------------
  1. Go to Configuration -> Search and Metadata -> Multiple sitemap
    (admin/config/search/multiple-sitemap)
  2. Create sub file and add the links.
  3. Save form.

  To create xml files first time run cron.Files will update automatically
  when your cron run.

  -- SUBMIT SITEMAP FILE TO SEARCH ENGINES --
  Add this lines in your robots.txt
    User-agent: *
    Sitemap:
    http://www.example.com/sites/default/files/multiple_sitemap/sitemap.xml


MAINTAINERS
--------------
Jitendra Jain (https://www.drupal.org/u/jitujain)
