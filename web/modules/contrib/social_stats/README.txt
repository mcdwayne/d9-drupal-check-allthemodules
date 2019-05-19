------------------------
SOCIAL STATS
------------------------

This is a statistics module. It provides data from various social
media sites. The data which is saved per node includes data from:
1. Facebook    : Likes count, shares count, comments count & total count.
2. LinkedIn    : Share count.
3. Google Plus : Plus one count
4. Total Count: Facebook + LinkedIn + Google Plus

------------------------
MODULE STRUCTURE
------------------------

1. social_stats : This module is responsible to collect data from the
   social sites and store it in the database. Views integration of
   social_stats module.

------------------------
REQUIREMENTS
------------------------

1. Social Stats : This module requires a supported version
   of Drupal and cron to be running.

------------------------
INSTALLATION
------------------------

1. Download the module and place it with other contributed modules
   (e.g. modules/contrib).

2. Enable the Social Stats module on the Modules list page. Appropriate
   tables will be created in the background.

3. Modify permissions on the People >> Permissions page.

4. Go to admin/config/social-stats/settings, set the date after which
   you want your data to be fetched. Select the social sites to be tracked
   per content type.

5. Run cron. This will fetch the statistics per node and store it in database.

------------------------
USAGE
------------------------

The administrative interface is at: Administer >> Configuration >>
Social Stats >> Social Stats settings.

Social Stats module will enable you to have the following additional
features to views.
1. Field : Add social statistics of node as field. Under "Add field",
   click on the category "Social Stats", and select the data.
2. Filter : Filter the content on the stats. For example, you can have a
   filter like "only show nodes which have Facebook likes > 200".
3. Sort : Sort the content on the basis of the stats, ascending or
   descending.

------------------------
RECOMMENDED MODULE
------------------------
Elysia Cron (https://drupal.org/project/elysia_cron) - It can be used to set
the intervals in which the data can be fetched. Can be set to the time when
the site is expecting least visitors. This will increase the performance.
