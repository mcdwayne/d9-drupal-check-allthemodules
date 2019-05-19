Name: UpTime Widget (uptime_widget)
Author: Martin Postma ('lolandese', https://drupal.org/user/210402)
Drupal: 8.x


-- SUMMARY --

A block showing the uptime ratio of the site (e.g. 99,87%).

It uses the service from UptimeRobot.com:
"Monitors your websites every 5 minutes, totally free.
Get alerts by e-mail, SMS, Twitter, RSS or push notifications for iPhone/iPad."


-- INSTALL --

Install as usual, see
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
for further information.

Enable the module at '/admin/modules'.


-- CONFIGURE --

Main configuration at 'admin/config/system/uptime-widget'.
After initial configuration set up visit 'admin/structure/block',
add 'Uptime' block and configure it.


-- CUSTOMIZE --

To change the content in the widget (e.g. to put the ratio first):
1. Copy the uptime-widget-block.html.twig file to your theme's template folder.
2. Make your changes.
3. Clear the site cache at 'admin/config/development/performance'.

To change the style of the widget (e.g. colors or icons):
1. Implement hook_uptime_widget_type_alter() in your THEME.theme file.
2. After adding new widget TYPE, create new file in theme's template folder
 and name it uptime-widget-type--TYPE.html.twig.
3. Make your markup (see uptime-widget-type.html.twig).
4. Add your styles into your theme's custom CSS file.
5. Clear both your browser and site cache.


-- FEATURES --

Starting from 8.x-1.2:
1. Added an option to use token [uptime_widget:ratio] anywhere
 token tree is attached.
2. At 'admin/reports/status' added new block with additional
 Uptime Widget information.
