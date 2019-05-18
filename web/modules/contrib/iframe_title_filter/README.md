CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Maintainers


INTRODUCTION
------------
This text format filter helps ensure that embedded `<iframe>` tags include a `title` attribute, in order to comply with [WCAG guidelines](https://www.w3.org/TR/WCAG20-TECHS/H64.html). When an iframe does not have a title attribute, this filter parses the `src` attribute's URL and adds a title attribute that reads "Embedded content from [url]".

A number of Drupal filters generate iframes (e.g., [media](https://www.drupal.org/project/media), [video_filter](drupal.org/project/video_filter)), but their compliance with iframe accessibility requirements varies. This filter is meant to be a universal band-aid to this particular guideline.


REQUIREMENTS
------------

This module requires no modules outside of Drupal core.


INSTALLATION
------------

Install the iFrame Title Filter module as you would normally install a contributed Drupal
module. Visit https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
--------------
1. Go to "Configuration > Text Formats" and enable "Add missing titles to iFrames" on any text format for which you expect embedded iframes to be present. 
2. Under "Filter processing order," make sure that this filter is set to run after any HTML filtering, as well as after any filters that would be generating iFrames (e.g., video_filter).


MAINTAINERS
-----------

The iFrame Title Filter was created and is maintained by 

 * Tyler Fahey (twfahey) - https://www.drupal.org/u/twfahey
 * Mark Fullmer (mark_fullmer) https://www.drupal.org/u/mark_fullmer
