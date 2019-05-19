INTRODUCTION
------------
The User hash module allows you to create an individual hash for each user.

You can use the hash as a light weight user identification where you do not want
to use the Drupal login credentials, e.g. as an individual API Key for reading
insensitive content. The module does not implement such functionality. However,
it implements a function to compare hashes preventing from timing attacks.

INSTALLATION
------------
Install as you would normally install a contributed Drupal module. See:
https://drupal.org/documentation/install/modules-themes/modules-8
for further information.

CONFIGURATION
-------------
In Administration > Configuration > People > User hash
(admin/config/people/user_hash) configure which PHP hash algorithm to use
(default is sha256) and how many characters for the random string
(default is 32) when generating hashes.

In Administration > Configuration > People > Permissions
(/admin/people/permissions) grant permissions to use user hash.

HASH GENERATION
---------------
The user hash module adds update options on the user list page in
Administration > People (admin/people) for generating and deleting user hashes
for users with permission to use user hash.

A new user hash will replace an existing one. No need to delete the old one
first.

DISPLAY
-------
User profile:
You can hide or position the hash on a user profile in Administration >
Configuration > People > Account settings > 'Manage display' if the
Field UI module is enabled. The user hash is then displayed on a user profile
if the user has 'administer account settings' permission or if it is his own
account.

User list:
You can display the user hash on the user list page in Administration > People
by editing the corresponding view. Go to Administration > Structure > Views and
edit the page display of the 'People' view. Under 'Fields' add the field 'Data'
from the 'User' category, then select 'User Hash' from the module drop down list
and add 'hash' as the name of the data key.

AUTHENTICATION PROVIDER
-----------------------
You can use the hash (X_USER_HASH) together with the user name (X_USER_NAME)
for REST API Authentication.

HASH COMPARISON
---------------
Symfony provides StringUtils::equals(string $knownString, string $userInput)
for comparing hashes using a constant-time algorithm in order to prevent from
timing attacks:
http://api.symfony.com/2.7/Symfony/Component/Security/Core/Util/StringUtils.html

MAINTAINERS
-----------
Current maintainers:
 * Richard Papp (boromino) - https://drupal.org/user/859722
