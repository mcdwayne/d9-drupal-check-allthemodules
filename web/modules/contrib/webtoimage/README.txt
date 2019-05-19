CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Image Generation
 * Maintainers

INTRODUCTION
------------
 
 Provide a path to convert any url to image, using wkhtmltoimage library 
 https://wkhtmltopdf.org. This module was develop to run on Pantheon, but can 
 be used in any server. If you are using pantheon                                                                                                                                                   , follow the instruction 
 here https://pantheon.io/docs/external-libraries/ to use wkhtmltoimage

REQUIREMENTS
------------

Need wkhtmltoimage be installed on server. https://wkhtmltopdf.org/


INSTALLATION
------------

 * Install our module as usual, see
   https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-
   modules-find-import-enable-configure-drupal-8 for further
   information.
 * Install wkhtmltoimage library :
    wget https://github.com/wkhtmltopdf/wkhtmltopdf/releases/download/0.12.5/wkhtmltox_0.12.5-1.xenial_amd64.deb
    sudo apt-get install -f
    sudo dpkg -i wkhtmltox_0.12.5-1.xenial_amd64.deb
    
    Test wkhtmltoimage library :
    /usr/local/bin/wkhtmltoimage http://www.google.com google.jpg 
   

CONFIGURATION
-------------

 * In this current version, its necessary to have wkhtmltoimage installed. Set 
 the binary on /admin/config/webtoimage (Configuration -> Settings -> 
 webtoimage settings) with value : ( /usr/local/bin/wkhtmltoimage )

IMAGE GENERATION
--------------

* IMAGE Generation (at /webtoimage/generateimage?url=[absolute-path-to-generate]).
Full link example for /node/123: (www.example.com/webtoimage/generateimage?url=
[absolute-path-to-generate])
* Example for node/123 (at www.example.com/webtoimage/generateimage?url=www.
example.com/node/123)

MAINTAINERS
-----------

Current maintainers:

 * Mamdouh Botros (https://www.drupal.org/u/do7a)
