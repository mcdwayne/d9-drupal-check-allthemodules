INTRODUCTION
------------
This module allows some level of integration between your Drupal website and the Freshdesk support service.

INSTALLATION
------------
1. Install as usual, see the [Drupal help page](https://www.drupal.org/docs/8/extending-drupal-8/installing-contributed-modules-find-import-enable-configure-drupal-8) for further
information.
2. Go to the blocks page /admin/structure/blocks.
3. In any region add the Freshdesk Widget block.
4. Configure appearance, (currently only text values), and then save.
5. Configure regular visibilty settings for Drupal core blocks (optional).

Your widget should now be displayed on your site.

Requirements
-----------
As of 20 August 2018, Freshdesk has changed the time-to-live on hashes generated
for single sign on request to +/- 30 seconds. Any request made outside of that
time restriction will not be allowed to login to Freshdesk. Due to this short
amount of time, it is highly recommended that the server that hosts the Drupal
site this module is installed on be synchronized via the network time protocol (NTP).

MAINTAINERS
-----------
8.x-1.x:

* Alan Sherry (https://drupal.org/u/asherry)
* Brady Owens (https://www.drupal.org/u/perignon)