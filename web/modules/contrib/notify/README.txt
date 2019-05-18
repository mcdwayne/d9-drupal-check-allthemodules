NOTIFY README.txt
=================


CONTENTS OF THIS FILE
---------------------

* Introduction
* Requirements
* Installation
* Configuration
* Related projects & alternatives
* Maintainers


INTRODUCTION
------------

Notify is a simple, lightweight module for sending e-mail
notifications about new content and comments posted on a Drupal web
site.

* For a full description of the module, visit the project page:
  https://drupal.org/project/notify

* For more documentation about its use, visit the documentation page:
  https://www.drupal.org/documentation/modules/notify

* To submit bug reports and feature suggestions, or to track changes:
  https://drupal.org/project/issues/notify

If you enable node revisions (https://www.drupal.org/node/320614), the
notification e-mail will also mention the name of the last person to
edit the node.


REQUIREMENTS
------------

This module requires a supported version of Drupal and cron to be
running.


INSTALLATION
------------

1. Extract the notify module directory, including all its
   subdirectories, into directory where you keep contributed modules
   (e.g. /modules/).

2. Enable the notify module on the Modules list page.  The database
   tables will be created automagically for you at this point.

3. Modify permissions on the People » Permissions page.

   To set the notification checkbox default on new user registration
   form, or let new users opt in for notifications during
   registration, you must grant the anonymous user the right to access
   notify.

4. Configure general notification settings.  See the "Usage" section
   below for details.


CONFIGURATION
-------------

The administrative interface is at: Administration » Configuration »
People » Notify settings.

The Settings tab is for setting how often notifications are sent, and
for selecting notification by note type.  The Users tab is to review
and see per-user settings.

When setting how often notifications are sent, note that e-mail
updates can only happen as frequently as the cron is set to run.
Check your cron settings.

To manage your own notification preferences, click on the
"Notification settings" on your "My account" page.


RELATED PROJECTS & ALTERNATIVES
-------------------------------

Currently none for Drupal 8.


MAINTAINERS
-----------

Kjartan Mannes <kjartan@drop.org> is the original author.
Rob Barreca <rob@electronicinsight.com> was a previous maintainer.
Matt Chapman <matt@ninjitsuweb.com> is the project owner.
Gisle Hannemyr <gisle@hannemyr.no> maintains the Drupal 7 branch.

Marton Bodonyi (http://www.interactivejunky.com/),
Mark Lindsey,
John Oltman <john.oltman@sitebasin.com>,
Ward Poelmans <wpoely86@gmail.com>,
Ishmael Sanchez (http://ishmaelsanchez.com), and
Ajit Shinde (https://www.facebook.com/shinde.ajit)
contributed to the Drupal 7 port.
Vincent Rommelaars <vincent@hostplek.nl> contributed to the Drupal 8
port.
