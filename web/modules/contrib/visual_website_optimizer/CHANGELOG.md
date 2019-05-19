# Changelog - VWO Drupal module

### v8.x-1.0, 2018-09-21

It's been 2 years since the alpha was released and people are using it without
complaint; clearly it is time for a full release.

Minor bugfix included which prevented using async include of VWO code.

### v8.x-1.0-alpha1, 2016-09-03

Initial release of D8 version which should allow for generic use of the VWO code on a Drupal site. This is a slightly feature limited release as a few of the features added to 7.x-1.x are not available (yet).

There have been large changes to how javascript can be added to a site, with an outright ban on inline js, plus a move to footer for Drupal Core js. This prevents the VWO Smart Code from being inserted verbatim by a module, and if we include it at the top, will cause jQuery to be loaded twice.

The 8.x-1.x branch is based off a direct port of 7.x-1.x branch. The 8.x-2.x branch will fork off this release and use more of the Drupal Core functionality.

Changes from 7.x-1.4 version and some notes:

  - Synchronous code not available; default was Asynchronous anyway.
  - All instrcutions and external URLs updated to the new interface and terminology.
  - Global Custom URL not available as a setting - probably not that useful anyway.
  - Smart Code is added to FOOTER as this is where Drupal 8 adds such attachments.
  - The "Smart Code Checker" will FAIL to find the code installed for any version.

Thanks to IT-Cru who prompted me to get on with this port.

### v7.x-1.x and before

See version 7.x-1.4 for previous changelog entries:
http://cgit.drupalcode.org/visual_website_optimizer/tree/CHANGELOG.txt?h=7.x-1.4

