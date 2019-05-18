CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Installation
 * Configuration
 * Maintainers
 
INTRODUCTION
------------

This is very simple module to keep record of concurrent logins counts day-wise, Also sent the mail notification if concurrent login count reached on critical level.

INSTALLATION
------------
 
 * Install as you would normally install a contributed Drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------
 
 * Configure module on Configuration » Concurrent users notification configuration form:

   - You need to set up cron job (https://www.drupal.org/cron)
   - To view "Concurrent logged in user count history" click on www.example.com/concurrent-users-notification/history

 * Configure permission on www.example.com/admin/people/permissions#module-concurrent_users_notification


MAINTAINERS
-----------

Current maintainers:
 * Abhishek Vishwakarma (visabhishek) - https://www.drupal.org/user/896554
