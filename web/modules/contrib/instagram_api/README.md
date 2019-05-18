# Instagram API
[![CircleCI](https://circleci.com/gh/dakkusingh/instagram_api.svg?style=svg)](https://circleci.com/gh/dakkusingh/instagram_api)

This module integrates Instagram API with Drupal. 
This module provides a number of services that provide
access to various API endpoints and data.

## Install Module
```
composer require drupal/instagram_api
drush en instagram_api
```

## Getting Started with API
### Register your application with Instagram API.
* Visit https://www.instagram.com/developer/clients/manage/
* Register your application.

### Configure the Module
* Visit `/admin/config/media/instagram_api`
* Add the Client ID
* Add the Client Secret
* Save the form, once the form is saved 
you will see additional field called "Access Token"
* Click the link get Access Token.
* Once you have the Access Token auto saved in the form,
you are good to go.

## Services and Methods provided:

### API Client Service
Instagram API Client, this is the core connector between Drupal and 
Instagram. It uses Guzzle to request API calls.

`See: Drupal\instagram_api\Service\Client`

### Media Service
Service class for Instagram API Media. 
Exposes a number of Instagram Media APIs

`See: Drupal\instagram_api\Service\Media`

### Users Service
Service class for Instagram API Users. 
Exposes a number of Instagram Users APIs

`See: Drupal\instagram_api\Service\Users`

### Locations Service
Service class for Instagram API Locations. 
Exposes a number of Instagram Locations APIs

`See: Drupal\instagram_api\Service\Locations`

### Comments Service
Service class for Instagram API Comments. 
Exposes a number of Instagram Comments APIs

`See: Drupal\instagram_api\Service\Comments`

### Tags Service
Service class for Instagram API Tags. 
Exposes a number of Instagram Tags APIs

`See: Drupal\instagram_api\Service\Tags`
