# Flickr API Integration
[![CircleCI](https://circleci.com/gh/dakkusingh/flickr_api.svg?style=svg)](https://circleci.com/gh/dakkusingh/flickr_api)

Flickr API Integration for Drupal provides fully configurable 
image galleries from Flickr website.

## Requirements
This module requires Drupal 8.X and a Flickr API key.

## Installation
Work in progress

## Configuration
Work in progress

## Services and methods provided:

### API Client Service
Flickr API Client, this is the core connector between Drupal and 
Flickr. It uses Guzzle to request API calls.

`See: Drupal\flickr_api\Service\Client`

### Photos Service
Service class for Flickr API Photos. 
Exposes a number of Flickr Photos APIs

`See: Drupal\flickr_api\Service\Photos`

### Galleries Service
Service class for Flickr API Galleries. 
Exposes a number of Flickr Galleries APIs

`See: Drupal\flickr_api\Service\Galleries`

### Photosets Service
Service class for Flickr API Photosets. 
Exposes a number of Flickr Photosets APIs

`See: Drupal\flickr_api\Service\Photosets`

### People Service
Service class for Flickr API People. 
Exposes a number of Flickr People APIs

`See: Drupal\flickr_api\Service\People`

### Favorites Service
Service class for Flickr API Favorites. 
Exposes a number of Flickr Favorites APIs

`See: Drupal\flickr_api\Service\Favorites`

### Groups Service
Service class for Flickr API Groups. 
Exposes a number of Flickr Groups APIs

`See: Drupal\flickr_api\Service\Groups`

### Tags Service
Service class for Flickr API Tags. 
Exposes a number of Flickr Tags APIs

`See: Drupal\flickr_api\Service\Tags`
