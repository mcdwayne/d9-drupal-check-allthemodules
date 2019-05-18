********************************************************************
Server Disk Space Notification Module
********************************************************************
Original Author: Priyanka Attarde
Current Maintainers: Priyanka Attarde

********************************************************************
DESCRIPTION:

   The server disk space notification module provides email notification
   feature for server disk space and memory usage.

********************************************************************
PERMISSIONS:

   This module defines the "server disk space configuration" permissions.
   The "server disk space configuration" permission determines whether a
   user will be able to edit the "Server Disk Space Settings".

********************************************************************
REQUIREMENTS
********************************************************************

This module requires the following modules:

 * SMTP Authentication Support (https://www.drupal.org/project/smtp)
 Note: Any mailserver will work.
********************************************************************
INSTALLATION:

1. Place the entire server_disk_space_notification directory into your
   Drupal modules/ directory or the sites modules directory
   (eg site/default/modules)


2. Enable this module by navigating to:

     Administration > Modules

3. Configure Server Disk Space Settings by visiting:

    Administration > Configuration > System > Server Disk Space Settings
