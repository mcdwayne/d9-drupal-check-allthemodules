# Restconsumer

This module provides a simple abstracted javascript API for jQuery.ajax and Drupal.ajax.
It offers a consistent interface, multilingual support and some helpers for forms and uploads.
The API must be used by other modules to do anything benefecial.

## Installation
1. Install using `composer require drupal/restconsumer` or via drupal.org/project/restconsumer.
2. Either depend on `restconsumer/restconsumer` or `restconsumer/simple` in your modules library.
3. Use the API in your javascript code.

## Usage
### restconsumer/simple
The simple dependency loads `Restconsumer_Wrapper` class in the global javascript namespace.
You can then use this class to create objects tailored to your needs.

#### Example
    var consumer = new Restconsumer_Wrapper(); // Create new consumer
    consumer.setLang('fr'); // Set a language to prefix your endpoints (optional).
    consumer.authorized = true; // Set authorized to true if you want to skip token authorization (optional).
    consumer.get('/this/is/my/endpoint').done(function(data) {
      // Do something
    });

### restconsumer/restconsumer
The restconsumer/restconsumer will initialize with multilingual support and integrate itself with the Drupal javascript object under the key `Drupal.restconsumer`.
This is the usual way and provides you with a fully loaded and authorized object to start accessing Drupal's rest resources.

#### Example
    Drupal.restconsumer.get('/this/is/my/endpoint').done(function(data) {
      // Do something
    });
