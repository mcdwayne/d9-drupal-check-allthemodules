CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Use Case
 * Requirements
 * Features
 * Tutorials
 * Known Issues


INTRODUCTION
------------

OpenID Connect REST module provides a REST API for the OpenID Connect module
and provides an authorization token using the Simple OAuth module.


USE CASE
--------

Let's assume you have multiple front-ends (webapp, mobile apps) using a
common Drupal back-end. Your other front-ends use Simple OAuth to authenticate
users against your Drupal back-end.

The Drupal back-end provides an authentication using OpenID Connect using third
party identity providers. But you want to enable authentication from other
front-ends with those third party identity providers.

Use the OpenID Connect REST module to get the third party identity providers
endpoints. Then, on your front-ends, open a new window loading this endpoint
(the user authenticates himself). The provider redirects the user to your
Drupal backend. Using JS, Intercept the authorization codes. Then, make a
HTTP request your Drupal backend with theses codes to get your OAuth token.
Finally, authenticate the user against your Drupal back-end with this OAuth
token.


REQUIREMENTS
------------
 * Drupal 8.x
 * Simple OAuth 8.x-2.0 : https://www.drupal.org/project/simple_oauth
 * OpenID Connect 8.x-1.0 : https://www.drupal.org/project/openid_connect


FEATURES
--------

PERMISSIONS :

 * The module adds a new permission section entitled
   OpenID Connect REST API with three new user rights :

   - Administer OpenID Connect REST : Allows access to new tabs on the 
     OpenID Connect configuration page.

   - Provides an api endpoint to provider ids : Allows access to an API
     endpoint returning the provider ids (facebook, google, generic, ...)

   - Provides an api endpoint to a provider authorization endpoint : Allows
     access to an API endpoint which provides an identity provider URL
     according to an identity provider id.

REST API ROUTES :

Available new REST routes are :

- GET /openid-connect/rest/provider-ids : Returns the OpenID Connect
  identity providers identifiers list.

- GET /openid-connect/rest/authorization-endpoint/{provider_id} : {provider_id}
  is an OpenID Connect identity provider identifier. Returns an OpenID Connect
  identity provider target URL.

- POST /openid-connect/rest/oauth/token/{provider_id} : {provider_id} is an
  OpenID Connect identity provider identifier. Returns an OAuth authorization
  token.

IDENTITY PROVIDER CALLBACK :

Configure your third party identity provider with this URL :

 - GET /openid-connect/rest/{provider_id} : {provider_id} is an OpenID Connect
   identity provider identifier. Authenticates the user locally according to a
   third party authorization code.

ADMINISTRATION :

The module provides two new tabs on the OpenID Connect
configuration page :

- State Tokens (for REST API) : Shows the stored OpenID Connect state tokens
  used by OpenID Connect REST to validate any request originating from an
  identity provider callback URL call.

- Authorization Mappings (for REST API) : Shows the stored authorization
  mappings used by OpenID Connect REST to validate any request from a
  front-end app towards OpenID Connect REST OAuth API.


TUTORIALS
---------

To use this module :
  I. Install and configure :
    1. Install and configure Simple OAuth.
    2. Install and configure OpenID Connect.
    3. Install OpenID Connect REST like any D8 module.
    4. Let's assume you have configured the Generic OpenID connect identity
       provider with the OpenID Connect identifier generic...
    5. Provide the OpenID Connect REST callback URI
       GET /openid-connect/rest/generic to your identity provider.

  II. First let the user authenticate himself on the Drupal back-end from one
      of your alternate front-ends using the OpenID Connect REST API :
    1. Request GET /openid-connect/rest/provider-ids to get the provider ids.
       In this case you should get a JSON response like this one :
        {
          "generic": "Log in with Generic"
        }
    2. Use the identity provider id (generic) to get the identity provider
       target URL : GET /openid-connect/rest/authorization-endpoint/generic.
       You should get a JSON response like this one :
        {
          "target_url":
          "https://my-identity-provider.tld/api/?client_id=44b9d345eger4ze4ffzdf4324fz3df34e07bb417045fgs54g45s1fc688d665&response_type=code&scope=openid%20email%20profile&redirect_uri=http://my-drupal-back-end.tld/openid-connect/rest/generic&state=tPpcXqzZVFsuUjg-LJEtdNTUiza0BUIEHSeyJZZvAr8&nonce=fa1d280df3ff0be8ff087fa202d348c7"
          ,
          "components": {
            "base_url":"https://my-identity-provider.tld/api",
            "parameters": {
              "client_id":
              "44b9d345eger4ze4ffzdf4324fz3df34e07bb417045fgs54g45s1fc688d665",
              "response_type": "code",
              "scope": "openid email profile",
              "redirect_uri":
              "http://my-drupal-back-end.tld/openid-connect/rest/generic",
              "state": "tPpcXqzZVFsuUjg-LJEtdNTUiza0BUIEHSeyJZZvAr8", // Drupal
                                                                      back-end
                                                                      security
                                                                      token
              "nonce": "fa1d280df3ff0be8ff087fa202d348c7"
            }
          }
        }
    3. Open a new window on your front-end and Request the target_url.
    4. The user proceeds with his authentication on the identity provider
       website...
    5. The new window gets a redirect response from the identity provider to
       the OpenID Connect REST callback URI with two params : code and state.
       For instance  :
        http://my-drupal-back-end.tld/openid-connect/rest/generic?code=fzerf45ezf4f6zef4565e4f6zf65z4fz5e6f4z6f&state=tPpcXqzZVFsuUjg-LJEtdNTUiza0BUIEHSeyJZZvAr8
       (state is the Drupal back-end security token)
    6. The user gets authenticated on the Drupal back-end.

  III. Then,  authenticate the user on your alternalte front-end using the
       OpenID Connect REST API :
    1. In the new window intercept with some neat JS code the code and state
       values.
    2. Keep these values, the new window isn't needed anymore. You can close it.
    3. You need your Simple OAuth client_id and secret_id.
    4. Now, request the OpenID Connect REST API 
       POST /openid-connect/rest/oauth/token/generic with in its body the code,
       state, client_id and client_secret values.
    5. You should get exactly the same JSON authentication response you would
       get from a request to Simple OAuth (with the OAuth token,
       expiration date, ...)
    6. Proceed with a Simple OAuth identification using the Simple OAuth token.


KNOWN ISSUES
------------
- No reports yet.
