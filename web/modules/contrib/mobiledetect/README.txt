MobileDetect service module; README.txt

The module provides the Mobile Detect PHP library.
The library provides the Mobile Detect class.
This class provides a device detection for mobile devices and the module makes
this available as a Drupal service.

This is a developer module and provides no end-user functionality.
Install it only if it required by a other module, or wish to use
the service in your own code.

DEPENDENCIES

Drupal 8
Mobile Detect library: https://github.com/serbanghita/Mobile-Detect

INSTALLATION

1. Download the Mobile Detect library.

a) Download the Mobile Detect library and unpack the file.
b) Rename the library file 'Mobile_Detect.php' to 'MobileDetect.php'.
c) Open the library file, find the line that contains

   class Mobile_Detect

   and change the line to

   class MobileDetect

2. Install the Mobile Detect library.

a) Create the directory libraries/mobiledetect/src if it not exist.
b) Copy the renamed and changed library file to the directory - see the 
   following structure.

Finally, there MUST exist the following structure:

libraries/mobiledetect/src/MobileDetect.php

3. Install and enable the MobileDetect service module.

ADMINISTER

No module administration available.

USAGE - PHP examples

@code
$service = \Drupal::service('mobiledetect');
$service->isMobileDevice();
$service->isTabletDevice();
$service->getUserAgent();
@endcode

See more examples: http://mobiledetect.net

@code
$detect = \Drupal::service('mobiledetect')->detect();
$detect->isMobile();
$detect->isTablet();
$detect->getUserAgent();
@endcode

@code
$detect = new \MobileDetect;
$detect->isMobile();
$detect->isTablet();
$detect->getUserAgent();
@endcode

@code
use MobileDetect;
$detect = new MobileDetect;
$detect->isMobile();
$detect->isTablet();
$detect->getUserAgent();
@endcode

CRON

The module provides an update information for the Mobile Detect library.
A detected new Mobile Detect vendor version will be reported to the:
- Status report page
- Recent log messages (if Database Logging module enabled)
The cron interval to ckeck a new vendor version is set to 14 days,
available as 'mobiledetect.settings.interval_days'.
