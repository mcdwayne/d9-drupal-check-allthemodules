CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Recommended Modules
 * Installation
 * Setup in AWS
 * Configuration
 * Maintainers


INTRODUCTION
------------

The Amazon Cognito module provides an integration against Amazon Cognito.

When this module is enabled, all user authentication flows are handled through
Cognito, including User Registration, User Login, and Password Reset.

 * For a full description of the module visit:
   https://www.drupal.org/project/cognito

 * To submit bug reports and feature suggestions, or to track changes visit:
   https://www.drupal.org/project/issues/cognito


REQUIREMENTS
------------

This module requires the following module outside of Drupal core:

 * External Authentication - https://www.drupal.org/project/externalauth


RECOMMENDED MODULES
-------------------

 * OpenID Connect - https://www.drupal.org/project/openid_connect
   This module can be used to allow logging in with other identity providers.
   Install OpenID Connect and assign a domain to your User Pool. This way,
   you'll use the AWS hosted login form which can handle signing users in with
   Facebook/Google/SAML and your own User Pool which this module will be
   registering users into directly.


INSTALLATION
------------

 * Install the Amazon Cognito module as you would normally install a
   contributed Drupal module. Visit https://www.drupal.org/node/1897420 for
   further information.


SETUP IN AWS
------------

Step 1: Create AWS key and secret
1. Go to https://console.aws.amazon.com/iam/home?#/security_credential
2. Create new key and secret from the "Access keys (access key ID and secret
  access key)" tab

Step 2: Creating user pool and app
1. Go to https://eu-central-1.console.aws.amazon.com/cognito/home
2. Go to manage your user pools
3. Create a user pool
4. Choose "Step through the settings"
5. Select "Email address or phone number"
6. Make "Email" required
7. Go through the next steps using default settings until you reach the create
  an app step
8. Add a new app
9. Uncheck "Generate client secret"
10. Check "Enable sign-in API for server-based authentication
  (ADMIN_NO_SRP_AUTH)"
11. Create app
12. Go through the next steps and create pool

Step 3: Get the user pool id and client id
1. Get the User pool id from the "General Settings" tab of your user pool to
  place inside your settings.php (see below)
2. Get the client id from from the "App clients" tab of your user pool to place
  inside your settings.php (see below)


CONFIGURATION
-------------

Currently the module only supports a "Email" Cognito flow process, which
basically means email is used for the unique identifier. You must set this when
you create your User Pool, it cannot be changed later. In the future support
for a "Username" flow will also be added.

Here are the relevant configuration details that are required:

```
$settings['cognito'] = [
  'region' => 'us-east-2',
  'credentials' => [
    'key' => '',
    'secret' => '',
  ],
  'user_pool_id' => 'us-east-2_XXXXXXX',
  'client_id' => '',
];
```


MAINTAINERS
-----------

 * Ben Dougherty (benjy) - https://www.drupal.org/u/benjy

Supporting organization:

 * Unearthed - https://www.drupal.org/unearthed
