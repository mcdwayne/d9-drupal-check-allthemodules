CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

The External link change module provides an input filter which allows to add
prefix and suffix to an external url. The user has to provide the list of
domains and their corresponding prefixes and suffixes to be added to the urls.

 * For a full description of the module visit:
   https://www.drupal.org/project/external_link_change

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/external_link_change


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

 * Install the External link change module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


CONFIGURATION
-------------

To enable use of the module:

    1. Navigate to Administration > Extend and enable the module.
    2. Navigate to Administration > Configuration > Content Authoring > Text
       formats and editors. A list will appear of the various input
       formats the site uses. Select the format to edit and select the
       "Configure" link.
    3. Scroll down to the "Enabled filters" field set and check the "External
       link change" to add prefix and suffixes to external URLs.
    4. In the "Filter processing order" field set, drag "External link change"
       to the top of the list using the draggable arrows. The reason for having
       this filter at the top is that it will work more optimally without having
       to process a URL twice. If Javascript is disabled, use the weight menu.
    5. Save configuration.

Basic configurations:

    1. Navigate to Administration > Configuration > Content Authoring > Text
       formats and editors. Select the format to edit and select the
       "Configure" link.
    2. In the "Filter settings" vertical tab group select the "External link
       change" tab.
    3. In the "Domains to use" field add a comma separated list of domains.
    4. In the "Prefix text" field set add a comma separated list of prefixes
       corresponding to respective domain names. Enter prefix values equal to
       entered domain values. Enter the null at the position where you do not
       want to add prefix.
    5. In the "Suffix text" field add a comma separated list of suffixes
       corresponding to respective domain names. Enter suffix values equal to
       entered domain values. Enter the null at the position where you do not
       want to add suffix.
    6. Save configuration.

For example: If you want to add a prefix and suffix to the Google and Yahoo URLs
then enter these values in the configuration fields:

 * Domain: google,yahoo
 * prefix: pregoogle,preyahoo
 * suffix: &googlesuf,&yahoosuf

After saving the configuration the urls will look like this:

 * Original: http://www.google.com
             http://www.yahoo.com
 * Changed:  pregooglehttp://www.google.com&googlesuf
             preyahoohttp://www.yahoo.com&yahoosuf


MAINTAINERS
-----------

 * hemant gupta (guptahemant) - https://www.drupal.org/u/guptahemant
