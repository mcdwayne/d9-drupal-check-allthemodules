
--------------------------------------------------------------------------------
                                 Purge Users
--------------------------------------------------------------------------------

OVERVIEW
--------
Auto Purge Users
Auto Purge Users lets administrators delete inactive users
based on time conditions. Users are selected as per criteria
that check for different types of user inactivity.
Some of the criteria that are used to select users are

•    Those who exceed a configured period of inactivity 
•    Those who have not activated their account since registration 
•    Those who have not logged in for a long period can be auto-purged.

The users who are purged can be notified that their account
has been purged. Optionally you can limit the purge to specific
roles like anonymous, etc.. UsersYou can go to
“Administer -> People -> Auto Purge Users” to configure the duration
 of account inactivity, status of account and filter the users by roles.

Users can be deleted automatically on cron by enabling
the auto purge option in configuration page or can be
deleted manually by pressing the delete users button.
Users deleted during cron are logged via the watchdog.


Installation
------------

1. Extract the tar ball that you downloaded from Drupal.org.

2. Upload the entire directory and all its contents to your
   modules directory.
   
3. Go to Admin -> Modules, and enable Purge Users.


Configuration
-------------

* "PURGE USERS WHO HAVE NOT LOGGED IN FOR" will purge users
  who created a account but never logged in.

* "PURGE USERS WHOSE ACCOUNT HAS NOT BEEN ACTIVATED FOR"
  will purge the users who didn't activated their account.

* "PURGE USERS WHO HAVE BEEN BLOCKED FOR" will purge
  the users who were using the site but were blocked due to any reason.

* Select a user role to limit the users to be purge to a specific role.

* User notification email can be enable by checking the "Enable" checkbox.
