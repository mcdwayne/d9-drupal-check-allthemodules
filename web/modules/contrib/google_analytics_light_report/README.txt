
GOOGLE ANALYTICS LIGHT REPORT MODULE FOR DRUPAL 7.x
---------------------------------------------------

CONTENTS OF THIS README
-----------------------

   * Description
   * Requirements
   * Installation Instructions
   * Credit


DESCRIPTION:
------------
This module integrates with Google Analytics Report API and
provides different reports of Google analytics with Pie and Line chart.

It uses "google-api-php-client" Library for Google Analytics
Core Reporting API integration.

It will create three different blocks for google analytics report.

It will show:
1. Google analytics report count for User,Seession, Bounce Rate and Pageviews
2. Google analytics report for Pageviews List.
3. Top Browsers (by pageview) using Pie Chart.

There are also an option to set duration in block configuration.

Also, It will create a page(/analytics-light-report) to show
the different types of google analytics report.



REQUIREMENTS:
-------------
Follow these two steps for install the third party "Google APIs Client"
library:-

1. Create a folder name must be 'google-api-php-client' in "sites/all/libraries"

2. Download and Extract the "Google APIs Client" Library into the
   'google-api-php-client' directory
   (usually "sites/all/libraries/google-api-php-client").
   Link: https://github.com/googleapis/google-api-php-client/releases
   Direct link for Download:
   https://github.com/googleapis/google-api-php-client/releases/download/v2.2.2/google-api-php-client-2.2.2.zip




INSTALLATION INSTRUCTIONS:
--------------------------
1. Put the module in your Drupal modules directory and enable it
   in admin/modules.

2. Go to admin/config/system/google-analytics-light-reports-api and
   follow the instruction to genrate service-account-credentials.json 
   file and upload this file.

3. After uploading service-account-credentials.json you will able to
   choose account, property and view for your google analytics account. 

4. Go to: /admin/structure/block and assign 'Google analytics top browser pie
   chart','Google analytics report for Pageviews List' and 'Google analytics
   report for Pageviews List' block according to your needs.

CREDIT
-------
babusaheb.vikas - Douce Infotect Pvt. Ltd.
Amcharts - https://www.amcharts.com/
