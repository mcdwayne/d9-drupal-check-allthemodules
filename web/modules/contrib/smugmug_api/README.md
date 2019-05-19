# SmugMug API
[![CircleCI](https://circleci.com/gh/dakkusingh/smugmug_api.svg?style=svg)](https://circleci.com/gh/dakkusingh/smugmug_api)

This module integrates SmugMug API with Drupal. 
This module provides a number of services that provide
access to various API endpoints and data.

## Install Module
```
composer require drupal/smugmug_api
drush en smugmug_api
```

## Getting Started with API
### Register your application with SmugMug API.
* Visit https://api.smugmug.com/api/developer/apply
* Register your application.

### Configure the Module
* Visit `/admin/config/media/smugmug_api`
* Add the API Key
* Add the API Secret
* Save the form, once the form is saved 
you are good to go.

## Services and Methods provided:

### API Client Service
SmugMug API Client, this is the core connector between Drupal and 
SmugMug. It uses Guzzle to request API calls.

`See: Drupal\smugmug_api\Service\Client`

### Album Service
Service class for SmugMug API Album. 
Exposes a number of SmugMug Album APIs

`See: Drupal\smugmug_api\Service\Album`

### User Service
Service class for SmugMug API User. 
Exposes a number of SmugMug User APIs

`See: Drupal\smugmug_api\Service\User`

### Image Service
Service class for SmugMug API Image. 
Exposes a number of SmugMug Image APIs

`See: Drupal\smugmug_api\Service\Image`

### Node Service
Service class for SmugMug API Node. 
Exposes a number of SmugMug Node APIs

`See: Drupal\smugmug_api\Service\Node`
