Introduction
-----------
Logman is a module which provides a better UI for searching dblog based watchdog
messages and includes apache log search. The UI is intended to provide easy
search of log messages with more information. The UI can be used to search both
the watchdog and apache log messages. This watchdog search tool provides 
searching ability on almost all fields of watchdog table. Similarly apache
access log search tool provides searching facility on most of the common fields.

Module provides a UI on every page indicating count of number of dblog based
watchdog messages grouped by severity.

Module also provides a statistics page with google column charts on dblog based
watchdog and apache access log data.

Requirements
------------
Drupal Core with dblog, jquery_ui and date_popup module.

Installation
-------------
Enable the module. Set your apache access log path in the settings page of the 
module. For example the commonly found apache access log path is 
'/var/log/httpd/access_log'. However you need to set the access log path
according to your server. You would also need to specify number of characters
to be read from the apache access log file, the default is 100000, you can
decrease or increase it, but a very high value may slowdown the system on
apache log search and statistics.
