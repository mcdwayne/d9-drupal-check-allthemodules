# HubSpot API

This module provides a way to load interact with the HubSpot PHP library which connects to the HubSpot API.

## Setup

After installing the module, you will need to provide an API key.

Go to `admin/config/services/hubspot-api` and provide a key.

Using Drush:
`drush cset hubspot_api.settings access_key "demo"`

## Demo

HubSpot provides a test account described in, `https://developers.hubspot.com/docs/overview`.

You can use the `demo` API key to access the demo account.

## HubSpot PHP library documentation

See `vendor/ryanwinchester/hubspot-php/README.md`

## Quick testing

You can use the following Drush command to test the setup. This loads the service, creates a handler for the API and then loads a list of contacts.

```
drush ev '$manager = \Drupal::service("hubspot_api.manager"); $handler = $manager->getHandler(); print json_encode($handler->contacts()->all(["count" => 10,"property" => ["firstname", "lastname"]]));'
```
