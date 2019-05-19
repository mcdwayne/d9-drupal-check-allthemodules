CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Installation
 * Using the module



INTRODUCTION
------------

Current Maintainers: cecrs, acrollet

The Site Alert module is a lightweight solution for allowing
site administrators to easily place an alert on their site,
for example for maintenance downtime, or any general
informational message. Alerts have start and end date/times,
and can be assigned a severity level. Messages are refreshed by
ajax and not subject to site caching, so changes made in the
ui will be automatically displayed to users without
necessitating a cache clear. The module provides site alert entities,
and a block that will display all active site alerts.


INSTALLATION
------------

Installation requires nothing more then enabling the site alert module.
Simple!


USING THE MODULE
----------------

Enable the Site Alert module
Add one or more site alert entities: admin/config/system/alerts
Add the 'Site Alert' block to whichever region(s) you wish it to appear in.
Ensure that all necessary roles have the 'administer site alerts' permission.
(All roles can view alerts)
Enjoy your exciting new site alert(s)!
