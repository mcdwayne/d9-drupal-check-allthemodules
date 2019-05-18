# Okta API Integration
[![CircleCI](https://circleci.com/gh/dakkusingh/okta_api.svg?style=svg)](https://circleci.com/gh/dakkusingh/okta_api)

The Okta API Integration Module provides Okta API functionality as 
Drupal Services to manage the following:

* Users
* Applications
* User assignments
* Group assignments

This module provides a settings form where you can configure the API 
Token and other settings for your Okta account.

Out of the box this module will provide services that integrate with 
Okta API, these services can be called from your custom modules. 
By itself this module does not provide any "functionality" as its is 
an API wrapper module.

## Okta API Reference
https://developer.okta.com/docs/api/resources/apps.html

## Installation
Install the Okta API Module
`composer require drupal/okta_api`

## Usage
### Okta API Service as Dependency Injection
In your custom service, you can use Dependency Injection
```
my_module.my_service:
  class: Drupal\my_module\FooBarService
  arguments: ["@okta_api.okta_users"]
```
### Okta API Service without Dependency Injection
Alternatively, just call the service without Dependency Injection.

`$okta_user = \Drupal::service('okta_api.users')->userGetByEmail('email');`

## Okta as IDP for SimpleSaml
This module is not the one to use if you are looking for IDP 
integration between Drupal and Okta.

Please use:
https://www.drupal.org/project/simplesamlphp_auth
