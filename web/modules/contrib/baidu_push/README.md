BAIDU PUSH
==========

INTRODUCTION
------------

The Baidu Push module allows to let the [Baidu](https://www.baidu.com) search
engine (the most used search engine in China Mainland) know about your
publicly accessible pages.

It adds a small JavaScript to your publicly accessible pages that pushes the
page URL to Baidu, once the page is visited in a JavaScript-enabled browser.

Rather than manually submitting a sitemap.xml file or waiting for Baidu to
visit your site and find all your pages, you can now let Baidu know about your
content pages in this more convenient way.


FEATURES
--------

  * Add Baidu Auto Push JavaScript to publicly accessible pages.
  * Define conditions for not adding Auto Push to low-quality pages
    (e.g. login/password reset/contact forms).


REQUIREMENTS
------------

No special requirements.


INSTALLATION
------------

  * Install the module as you would do with any other Drupal module.  
    If you install using composer, you can use:  
    `composer require "drupal/baidu_push"`  
  * Enable the module.  


CONFIGURATION
-------------

  * Go to the baidu_push settings and enable the Auto Push service at  
    Administration / Configuration / Search and metadata / Baidu Push  


ROADMAP
-------

  * Utilize Baidu Active Push API to regularly update all page URLs via your
    Baidu Webmaster Tools account.


MAINTAINERS
-----------

  * [Mario Steinitz](https://www.drupal.org/u/mario-steinitz)


SUPPORTING ORGANIZATIONS
------------------------

[SHORELESS Limited](https://www.drupal.org/shoreless-limited)
SHORELESS Limited is an IT consulting and software solutions provider. The
development of the initial version of this module was funded by SHORELESS to
integrate Drupal 8 based websites with Baidu.  

It also grants me paid working hours to further enhance and improve the module
according to the needs of the Drupal community.  
