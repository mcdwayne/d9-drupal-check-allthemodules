CONTENTS OF THIS FILE
----------------------

* Introduction
* Requirements
* Installation
* Configuration
* To-do
* Maintainers


Introduction
============

This module implements Google Search Console to optimize your content, will be alerted on issue and fix your site.
Optimize and enhance your site and improve your performance for site Multi-domain.
This module is inspired form the Domain Site Verification, and metatag, to implement best parts.
Use domain access to facilitate the use of Google Search console and Multi-domain site.

* For more information you can check the module here:
  https://www.drupal.org/project/md_site_verify

* Bug reports and feature suggestions are welcome, please track all changes in this link:
    https://www.drupal.org/project/issues/md_site_verify


Requirements
============

* Create an account on the concerned service and obtain verification files or base meta tag, use one of the following examples services:

GOOGLE
Create a Google Webmaster Tools Account:
* https://www.google.com/webmasters/tools/home
* https://support.google.com/webmasters/answer/35179

Bing
Create a Bing Webmaster Tools Account:
* http://www.bing.com/toolbox/webmaster
* http://www.bing.com/webmaster/help/how-to-verify-ownership-of-your-site-afcfefc6

Yandex
Create Yandex webmaster account:
* https://webmaster.yandex.com
* https://yandex.com/support/webmaster/service/rights.html


Installation
============

* To install a contributed Drupal please visit this page:
  https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
=============

To verify a site, please check this path on the following administration menu:
1. Administration > Configuration > Domains > Search > Verifications.
2. select "Add verification".
3. Select the search engine which you want to verify.
4. The "Verification META tag" is the full meta tag provided for site verification, it’s only visible in the source code of the front page.
5. There is an option to provide verification on a file. Either you can upload a file directly or provide its name and contents to "Verification file" and "Verification file contents" respectively. Site contents could be left empty to use default content.
6. Save.

To Edit an existing site verification:
1. Navigate to Administration > Configuration > Domains > Search > Verifications.
2. Select Edit next to the search engine to be edited.
3. Make appropriate edits.
4. Save.


TODO
=============
* Make the configuration user friendly and improve development for this Multidomain site verify configuration => Patches/issue/proposition are welcome :slightly_smiling_face:


Dependencies
=============
   - Module Domain


MAINTAINERS
===========

Current maintainers:
* fazni - https://www.drupal.org/u/fazni
