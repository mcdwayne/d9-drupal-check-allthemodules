INTRODUCTION
============

This module is providing a custom parser and a fetcher
for the Feeds module, adding support to import content from cision.
Cision Feed creates translations if a feed item has connected versions
in another language.

REQUIREMENTS
============

- Feeds 8.x-1.x
  http://drupal.org/project/feeds
- Drupal 8.x
  http://drupal.org/project/drupal

INSTALLATION
============

- Install and enable feeds and the cision_feed modules.

CONFIGURATION
=============

- Add a new feed type at admin/structure/feeds.
- Set the fetcher to Cision Fetcher.
- Set the parser to Cision Parser.
- Configure the settings for the fetcher.
- Use the node processor and choose which
bundle to use.
- Setup mappings under admin/structure/feeds/manage/<importer>/mapping.
- Add a new feed under admin/content/feed.
- Add the Feed URL used for the feed.

MAINTAINERS
-----------

Current maintainers:
 * Krister Andersson (Cyclonecode) - https://www.drupal.org/user/1225156
