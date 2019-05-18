--------------------------------------------------------------------------------
                                 Backup Permissions
--------------------------------------------------------------------------------

OVERVIEW
--------
Backup permissions
The Permissions Backup module allows you to backup permissions as a whole on a per role basis and save it so that it can be imported later on the same installation or another installation. Backup of permissions state can be created through Administration » People » Backup permissions page, where users can select role(s) to backup and select specific permissions state only if needed. The saved/downloaded permission state can be used to recover the permission states on your site to a previous moment in time, or just roll-back changes by enabled or disabled permissions.

Features
--------
Allows backups to be downloaded as CSV directly
Allows users to import permissions from CSV
Supports functionality to automatically backup permission every-time permissions are updated
Creates new role if it exists on the imported CSV and doesn't exist on the system
Provides functionality to select role and permissions state during permissions rebuild

Installation
------------

1. Extract the tar ball that you downloaded from Drupal.org.

2. Upload the entire directory and all its contents to your
   modules directory.

3. Go to Admin -> Modules, and enable Backup permissions.
