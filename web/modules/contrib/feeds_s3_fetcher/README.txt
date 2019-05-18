CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Feeds S3 Bucket Fetcher module provides a fetch method for the Feeds
module that can fetch a file directly from an Amazon S3 Bucket.

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/feeds_s3_fetcher

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/feeds_s3_fetcher


REQUIREMENTS
------------

This module requires the following outside of Drupal core.

* Feeds - https://www.drupal.org/project/feeds

* AWS SDK version-3. If module is installed via Composer it gets automatically
  installed.

* A working Access Key ID and Access Key in settings.php or as environment
  variables. In settings.php set feeds_s3_fetcher.access_key and
  feeds_s3_fetcher.secret_key accordingly.

  For setting credentials in environment variables, visit:
  https://docs.aws.amazon.com/sdk-for-php/v3/developer-guide/guide_credentials_environment.html


INSTALLATION
------------

 * Install the Feeds S3 Bucket Fetcher module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Structure > Feed types and add a feed.
    3. From the fetcher dropdown, select "Download from S3 Bucket".
    4. Select the Parser: CSV, OPML, RSS/Atom, or Sitemap XML.
    5. Select the Processor and the Content type.
    6. In the Settings field-set, select the Import period of how often a feed
       should be imported.
    7. Save.


MAINTAINERS
-----------

 * Marc Groth (marc.groth) - https://www.drupal.org/u/marc.groth
 * Roland Molnár (roland.molnar) - https://www.drupal.org/u/rolandmolnar

Supporting organization:

 * Technocrat - https://www.drupal.org/technocrat
