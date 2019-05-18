# BYU Resources

This module gets information about BYU's web components and drupal modules and displays them in a view using the BYU feature card component. It then updates the information on these resources with cron.

## Configuration

The configuration for this module can be changed by going to Configuration, then Development, and then BYU Resources Settings. The link is /admin/config/development/byu_resources. In that form you can change what fields should be updated on a daily basis. Currently you can only tell the module to update the documentation and short description fields. More options are to come.

  You can update the fields yourself without breaking anything.

## WARNINGS

- When you install the module, it can take up to five to ten minutes to finish installing. This is because when you are installing the module, it pulls all the information on the resources we have, and saves each of them as their own BYU resource node.

- There weren't any good API endpoints when developing this module. The information is either pulled from cdn.byu.edu or directly from drupal.org. It is likely that this module will break if any of the urls change or drupal.org gets altered in the way it's organized. What will happen is that the fields that are listed for update will simply be blank. We're working on a safegaurd to this.