CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Module Details
 * Recommended modules
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers


INTRODUCTION
------------
CSSGram is a simple module to apply CSS filters on image field type 
using field formatters for recreating Instagram filters with CSS filters
and blend modes. 


MODULE DETAILS
--------------
CSSGram Module uses CSSGram library for adding filter effects via CSS to
the image fields. Basically, module extends Field Formatter Settings to 
add image filter for that particular field. 
 
To configure image filters for a field, visit Manage Display settings
and use format column to specify the filter to be used for that image.



CONFIGURATION
-------------
As such module does not contain any site wide configuration.


TROUBLESHOOTING
---------------
As of now, CSSGram uses CSS for producing image filters and if the image
filters are not working properly there could be following issues:
* CSS override
* CSSGram css file not available.

FAQ
---
Q: Can I use different image filters for different fields?

A: Yes


Q: Can CSSGram filters be used as Image Effects in Image Presets?

A: No, CSSGram is a simple module which makes use of CSSGram library.


Q: Is the module compatible with views?

A: Once this issue gets fixed: https://www.drupal.org/node/2686145, it 
would become compatible with views too. 


Q: Can I use image filters seperately?

A: Yes, just add corresponding filter class to wrapper tag (preferably) 
of img tag and load the cssgram library on the page.


CREDITS
-------
Credit Goes to the creators of CSSGram Library (https://una.im/CSSgram)

MAINTAINERS
-----------
Current maintainers:

 * Purushotam Rai (https://drupal.org/user/3193859)


This project has been sponsored by:
 * QED42
  QED42 is a web development agency focussed on helping organisations and
  individuals reach their potential, most of our work is in the space of
  publishing, e-commerce, social and enterprise.