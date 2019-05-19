CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers
 * Credits


INTRODUCTION
------------

The Webform Auto Exports module provides the ability to export Webform
results automatically according to the configured schedule and email and/or
SFTP generate files to configured email address and/or SFTP location.

You can enable automatic export and do configurations form wise, which is
providing more control over forms and automatic export configurations.

Webform Auto Exports module developed by City of Casey (https://www.casey.vic.gov.au)
as a requirement of one of their Drupal websites.

 * For a full description of the module visit:
   https://www.drupal.org/project/coc_forms_auto_export

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/coc_forms_auto_export


REQUIREMENTS
------------

This module requires Webform modules outside of Drupal core and does require an
external PHP Secure Communications Library (phpseclib) to be downloaded and
installed if using SFTP functionality.


INSTALLATION
------------

    1. Download and install the Drupal module as normal.
    2. If you have composer you can install external library using
       "composer require phpseclib/phpseclib:~2.0".
    3. Otherwise, manually download the library zip file, using the URL on the
       site "Status report" page to ensure that you get the right version.
    4. Extract the files to sites/all/libraries, and rename phpseclib-master to
       phpseclib.
    5. Enable the module.

For more information on installing modules, visit:
https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
-------------

    1. Once the module has been installed and enabled, you can enable 'Automatic Export'
       individually on each Webform Results Download configuration page
       Administration > Structure > Webforms > Results > Downloads > AUTOMATIC CSV EXPORT.
    2. You can enable email exported results to a nominated email address and / or SFTP
       generated files to configured SFTP location.
    3. You can further configure the search criteria for the exporting results and schedule
       for the automatic export.


MAINTAINERS
-----------

 * Hasitha Guruge (ozwebapps) - https://www.drupal.org/u/ozwebapps


CREDITS
-------

 * City of Casey, Victoria, Australia - https://www.casey.vic.gov.au
 (this module is developed according to a requirement of City of Casey)
 * Mohamed Sathik (an author of the initial version of the module)
