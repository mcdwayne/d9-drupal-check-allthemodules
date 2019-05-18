# CONTENTS OF THIS FILE

 * Introduction
 * Requirements
 * Installation
 * Usage
 * Troubleshooting
 * Maintainers


## INTRODUCTION

The OAuth2 Client module allows for the creation of OAuth2 clients as Drupal
plugins, handling all back end functionality for retrieval, refresh, and
deletion of tokens

 * For a full description of the module, visit the project page:
   https://www.drupal.org/project/oauth2_client

 * To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/oauth2_client


## REQUIREMENTS

This module depends upon the PHP OAuth 2.0 Client library
(https://github.com/thephpleague/oauth2-client). This library will be installed
automatically if/when the module is downloaded and managed with Composer.
such, Composer is a requirement for installing this module.

 * Composer (https://getcomposer.org/)


## Installation

This module must be installed using the following composer command:

`composer require drupal/oauth2_client:^2.0`

## Usage

### Creating New Clients

As of version 8.x-2.x of the module, OAuth2 Clients are Drupal Plugins.
Plugins must be created in the `Plugin/Oauth2Client` namespace.
Plugins should extend the class: `Drupal\oauth2_client\Plugin\Oauth2Client\Oauth2ClientPluginBase.`

An example plugin declaration is as follows:

```
namespace Drupal\oauth2_client\Plugin\Oauth2Client;

/**
 * OAuth2 Client to authenticate with Instagram
 *
 * @Oauth2Client(
 *   id = "instagram",
 *   name = @Translation("Instagram"),
 *   grant_type = "authorization_code",
 *   client_id = "########",
 *   client_secret = "########",
 *   authorization_uri = "https://api.instagram.com/oauth/authorize",
 *   token_uri = "https://api.instagram.com/oauth/access_token",
 *   resource_owner_uri = "",
 *   scopes = ["basic", "firebase", "openid"],
 *   scope_separator = ",",
 * )
 */
class Instagram extends Oauth2ClientPluginBase {}
  ```

Fill in the various plugin keys with the relevant data. Keys:

 * id: This should be a unique plugin ID. There cannot be another @OAuth2Client
      plugin on the system with the same ID.
 * name: The Human-readable name of the plugin.
 * grant_type: The type of grant flow of the OAuth2 authentication server.
   Possible values are:
   * authorization_code (See: https://alexbilbie.com/guide-to-oauth-2-grants/#authorisation-code-grant-section-41)
   * client_credentials (See: https://alexbilbie.com/guide-to-oauth-2-grants/#client-credentials-grant--section-44)
   * resource_owner (See: https://alexbilbie.com/guide-to-oauth-2-grants/#resource-owner-credentials-grant-section-43)
 * client_id: The Client ID provided by the OAuth2 authentication server.
 * client_secret: The Client Secret provided by the OAuth2 authentication
   server.
 * authorization_uri: The Authorization URL on the OAuth2 authentication server.
 * token_uri: The Token URL on the OAuth2 authentication server.
 * resource_owner_uri: The Resource Owner URL on the OAuth2 authentication
   server. Leave blank if not provided by the OAuth2 authentication server.

Clear the cache/registry, and if you've done it correctly, your plugin will be
available for testing at Admin -> Reports -> OAuth2 Client -> Client Tester.

### Retrieving Access Tokens

To retrieve an access token, use the `getAccessToken()` method of the OAuth2
Service, passing it the Plugin ID of the OAuth2 Client for which token should be
retrieved. This will return a `\League\OAuth2\Client\Token\AccessToken` object
on which `getToken()` can be called.

```
$access_token = Drupal::service('oauth2_client.service')->getAccessToken($client_id);
$token = $access_token->getToken();
```

In the above example `$token` will contain the access token that can be used in
requests made to the remote server. The `getAccessToken()` method should be
called before making any requests, to ensure that the token is always valid.
This method will refresh the token in the background if necessary.

## Troubleshooting

This module provides a testing page upon which plugins can be tested to see if
they are configured correctly. This page can be found at Admin -> Reports ->
OAuth2 Client -> Client Tester (/admin/reports/oauth2_client/client_tester).
Tokens can also be cleared on this page if necessary.
