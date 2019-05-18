-- SUMMARY --

The Patreon module implements the patreon.php library
(https://github.com/Patreon/patreon-php) to connect a Drupal site with the
Patreon API. This allows other modules to access data from an authorised Patreon
(https://www.patreon.com) account, either the creator or their patrons.

-- REQUIREMENTS --

A client key and secret must be obtained by registering at
https://www.patreon.com/platform/documentation/clients. The module's endpoint
(<your site>/patreon/oauth) must be registered as an allowed redirect
destination in the client application.

The Patreon API library has a dependency on composer, which means that this
module must also be installed using composer. You can find instructions for
managing a Drupal 8 site using composer at https://www.drupal.org/node/2718229.

-- INSTALLATION --

The Patreon API library has a dependency on composer, which means that this
module must also be installed using composer. You can find instructions for
managing a Drupal 8 site using composer at https://www.drupal.org/node/2718229.

-- CONFIGURATION --

A valid client id and secret key must be added to the form at
/admin/config/services/patreon/settings, and access to the creator account when
prompted.

-- CUSTOMIZATION --

This module provides a Service linking Drupal and the Patreon API, allowing
other modules to provide functionality reliant on the API. Once configured,
the Service provides three main methods to obtain data from Patreon:

* ->fetchUser()
* ->fetchCampaign()
* ->fetchPagePledges()

Each returns an \Art4\JsonApiClient\Document obtained from the Patreon API. The Service
contains a Bridge object that contains helper functions. Values can be pulled from the
returned results using the bridge method ->getValueByKey().

The functions correspond to the documented functions provided by the patreon.php
library, and each uses the default user access token stored in the module's settings
when the creator authorises their account. Other access tokens can be passed to the
function to obtain patron information.

Custom modules can implement their own authorisation processes by using the Service's
->authoriseAccount() and ->getStoredTokens() methods, and overriding the ->getCallback()
method.

-- TROUBLESHOOTING --

If you are implementing a custom authorisation process and using
->authoriseAccount() fails to redirect the user to Patreon's
authorisation page, check that you have added your callback URL to the
Service by overriding ->getCallback().

-- CONTACT --

Current maintainer:

* Dale Smith (MrDaleSmith) - https://drupal.org/user/2612656