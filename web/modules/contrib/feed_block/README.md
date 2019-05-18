INTRODUCTION
------------

The Feed Block module displays listings from RSS feeds, each stored in Drupal as blocks, each with display configuration (i.e., number of items, whether to display date & descriptive text, etc.) The module minimally themes these blocks via a tpl.php file. For custom theming, this template can be copied into a sites active theme and modified.

 * For a full description of the module, visit the project page:
   https://drupal.org/project/feed_block

 * To submit bug reports and feature suggestions, or to track changes:
   https://drupal.org/project/issues/feed_block

REQUIREMENTS
------------
- Minimum PHP 5.5.9
- This module does not have any module dependencies.


CONFIGURATION
-------------
After enabling the module, create new feed blocks at /admin/structure/blocks.

This includes the settings to configure:
- Cache lifetime
- Date display format
- Descriptive text (teaser) cut-off
- Whether to render HTMl from the descriptive text in the feed

USAGE
-----
1. Go to Structure > Blocks > Add Feed Block (admin/structure/block/add-feed-block)
2. Fill out the form
3. If your feed URL is valid, a block will be created, which you can position
via your preferred method (i.e., the Block UI, Panels, Context, or something else)

MAINTAINERS
-----------
Current maintainers:
 * Mark Fullmer (mark_fullmer) - https://www.drupal.org/u/mark_fullmer
