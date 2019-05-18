INTRODUCTION
------------

This module generates a Google News sitemap specific to each domain feed based 
on the content created within the last 48 hours as default configuration.

NOTE : The content whoes domain source is not set will not be visible as google
new sitemap content.

REQUIREMENTS
------------

This module requires the following modules:

 * Domain Access (https://www.drupal.org/project/domain)

Features
-------------------------------------------------------------------------------
* Domain Google News -compatible sitemap XML output.

* Selection of content types to be output is configurable.

* Output XML file can be cached to reduce server load with a configurable timer.

INSTALLATION
------------

 * Install the Domain Google News sitemap module as you would normally install a contributed
   Drupal module. Visit https://www.drupal.org/node/1897420 for further
   information.


Configuration / Usage
-------------------------------------------------------------------------------
 1. Without any configuration, the module generates a Domain specific Google 
  News sitemap feed for all nodes from the past 48 hours at the following URL:
	 http://example.com/googlenews.xml

 2. All configuration is handled via the main settings page:
      admin/config/services/googlenews

 3. The following items may be controlled:

    * The publication name defaults to the site's name, this may be overridden.

    * By default all content types will be used in the sitemap, this may be
      changed as needed; as Google News expects only *news* articles, choose
      the content types wisely.

    * The publication name on the sitemap file defaults to the site name.

    * Only content created within the past 48 hours will be displayed, this can
      be changed though the default is recommended.

    * To aid with site performance, the file's output will be cached for 15
      minutes; this can be increased as necessary.

 4. Visit the sitemap file to confirm it contains the desired content:
      http://example.com/googlenews.xml

 5. Use the Google Webmaster Tools to submit the sitemap file per the official
    documentation:
      http://support.google.com/webmasters/bin/answer.py?hl=en&answer=74289


TROUBLESHOOTING
---------------
* If no content shows in the sitemap file then check that there is content
  matching the filters and within the time range.
