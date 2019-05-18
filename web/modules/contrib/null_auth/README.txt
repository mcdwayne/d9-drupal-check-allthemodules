Drupal Null Authentication Provider module
==========================================

## Summary
  This module is a null authentication provider. Enabling anonymous user access
  to Drupal 8 REST Resources, using an auto-login method.

  WARNING: This module does not control any flood and give auto-login for all
  request. Use only in environments with controlled access.

## Installation
  Place this module at <DRUPAL_ROOT>/modules/contrib/ and then install it.

## Configuration and usage instructions
  There is no configurations, this module enable automatically a null
  authentication method. You need to send _null_auth query parameter to all your
  requests.

  Using the contrib module REST UI, you can enable REST Resources using the
  Authentication Provider null, remember to enable the specific permissions for
  the REST Resource to anonymous user.

  Steps:
  1.- Get the X-CSRF-Token: GET /rest/session/token
     HEADERS:
       - Content-Type: application/json

  2.- Create a new user: POST /entity/user?_format=json&_null_auth=1
     HEADERS:
       - Content-Type: application/json
       - X-CSRF-Token: ---
     BODY:
     {
       "name":{
         "value":"userName"
       },
       "mail":{
         "value":"userMail"
       }
     }

## Sponsors
  * This Drupal module is sponsored and developed by http://cambrico.net/
    Get in touch with us for customizations and consultancy:
    http://cambrico.net/contact

## About the authors
  * Pedro Cambra (https://www.drupal.org/u/pcambra)
  * Manuel Egío (https://www.drupal.org/u/facine)
