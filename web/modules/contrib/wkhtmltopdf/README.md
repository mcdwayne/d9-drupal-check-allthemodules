CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Pdf Generation
 * Maintainers

INTRODUCTION
------------
 
 Provide a path to convert any url to pdf, using wkhtmltopdf library 
 https://wkhtmltopdf.org. This module was develop to run on Pantheon, but can 
 be used in any server. If you are using pantheon, follow the instruction 
 here https://pantheon.io/docs/external-libraries/ to use wkhtmltopdf

REQUIREMENTS
------------

Need wkhtmltopdf be installed on server. https://wkhtmltopdf.org/


INSTALLATION
------------

 * Install as usual, see
   https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-
   modules-find-import-enable-configure-drupal-8 for further
   information.
   

CONFIGURATION
-------------

 * In this current version, its necessary to have wkhtmltopdf installed. Set 
 the binary on /admin/config/wkhtmltopdf (Configuration -> Settings -> 
 Wkhtmltopdf settings).
 * If you are using Patheon and have configured https://pantheon.io/docs/
 external-libraries/ already, just add the link sites/all/libraries/
 wkhtmltopdf/wkhtmltopdf on Wkhtmltopdf settings.


PDF GENERATION
--------------

* PDF Generation (at /wkhtmltopdf/generatepdf?url=[absolute-path-to-generate]).
Full link example for /node/123: (www.example.com/wkhtmltopdf/generatepdf?url=
[absolute-path-to-generate])
* Example for node/123 (at www.example.com/wkhtmltopdf/generatepdf?url=www.
example.com/node/123)

MAINTAINERS
-----------

Current maintainers:

 * Julio Hidemi Kamizato (https://www.drupal.org/u/jkamizato)
