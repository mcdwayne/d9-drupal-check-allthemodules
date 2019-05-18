# BitLink Module for Drupal 8.

## Overview
This module provides the ability for Drupal websites to generate Bitly URLs. It allows user to shorten and expand URLs using Bitly API which can be used in site. It also provides an API which can be used by other modules to shorten or expand URLs programmatically. 

## Installation

To add this module in the site, add below code in composer.json,

```
"require" : {
  "drupal/bitlink": "^1.0",
},
```

## Syntax of JSON file for module configuration.
```json
{
  "api_base_url": "",
  "username": "",
  "password": "",
  "oauth_clientid": "",
  "oauth_clientsecret": "",
  "group_guid": ""
}
``` 

## Shorten URLs using Drupal interface.
To shorten the URLs via logged-in user, login to site as user having permission "Administer Bitlink Settings". Go to ```admin/config/bitlink/shorten``` and provide long URL in field and click Shorten URL. You will receive Short URL generated via Bitlink API that can be used anywhere in site.

## Shorten URLs using API.
To shorten the URLs programmatically using Bitlink API, just use below code in your custom module.

```php
<?php

$bitlink_service = \Drupal::service('bitlink.api_service');
$response_data = $bitlink_service->shorten($long_url);

```

## Expand URLs using Drupal interface.
To expand the URLs via logged-in user, login to site as user having permission "Administer Bitlink Settings". Go to ```admin/config/bitlink/expand``` and provide Short URL in field and click Expand URL. You will receive Expanded URL generated via Bitlink API.

## Expand URLs using API.
To expand the URLs programmatically using Bitlink API, just use below code in your custom module.

```php
<?php

$bitlink_service = \Drupal::service('bitlink.api_service');
$response_data = $bitlink_service->expand($short_url_id);

```

Note: ```$short_url_id``` is nothing but the Bitly Short URL without having HTTP protocol prefixed to it.
