INTRODUCTION
------------
The Discourse Synchronize module synchronizes Drupal roles to Discourse.
Roles are created, updated and deleted in Discourse when the corresponding role
in Drupal is edited. There is no synchronization the other way around,
i.e from Discourse to Drupal.

INSTALLATION
------------
Install as you would normally install a contributed Drupal module. See:
https://drupal.org/documentation/install/modules-themes/modules-8
for further information.

CONFIGURATION
-------------
In Administration > Configuration > System > Discourse SSO
(/admin/config/system/discourse_sso) set API Key, API Username and
Webhook secret from Discourse.

API Key and Username
--------------------
Refer to the Discourse documentation for how to create an API key for a user.

Webhook secret
--------------
Discourse has to communicate to Drupal when a new user is created in Discourse
through single sign on, in order to synchronize Drupal roles for that user.
Therefore you have to create a webhook in Discourse
(https://meta.discourse.org/t/setting-up-webhooks/49045) and set the payload url
to [drupal url]/discourse_sync/user/webhook:

1. Go to Discourse > Admin > API > webhooks (/admin/api/web_hooks)
2. Klick on "New Webhook"
3. Set payload url to [drupal url]/discourse_sync/user/webhook
4. Set the same secret phrase in discourse as in
   Drupal > System > Discourse SSO > Webhook secret
5. Select user event as individual event
6. Set active and save.

TODO
----
Only thing missing is an action on user delete. Discourse seems to delete users
only, if they don't have more than a few posts. Otherwise users are
anonymized instead.

MAINTAINERS
-----------
Current maintainers:
 * Richard Papp (boromino) - https://drupal.org/user/859722
