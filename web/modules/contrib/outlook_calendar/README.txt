
INTRODUCTION:
------------
This module is used to fetch outlook calendar events.

Multiple accounts can be configured and events of each accounts
will be fetched based on the account order.

REQUIREMENTS:
-------------
This module requires the following modules:

link - already enabled in core
date
libraries

INSTALLATION:
-------------
1. Place the entire outlook calendar folder into your Drupal modules/
   directory.

2. Enable the outlook calendar module by navigating to:

     extend > modules

CONFIGURATION:
--------------
1. Once the module is installed, you will find a outlook calendar link under
   Administration->Web Services
   here you can configure your outlook account from which the events
   have to be fetched.

2. After configuration, the events will be fetched and displayed under the path
   baseurl/outlook-calendar. Also there will be block named Outlook Calendar
   which will contain all events in a table format. you can configure the
   block under Structure->Block Layout.

IMPORTANT NOTE:
---------------
1. To install the php-ews library use the command
   composer require php-ews/php-ews

   This will install the necessary libraries into the vendor folder.

Author:
-------
Jack Ry
