# SESSION BASED TEMPORARY STORAGE

## INTRODUCTION.

This module is designed for developers only. It provides a service for storing
and retrieving temporary data for a given owner through the session.
  
This service can be used as like PrivateTempStore to make temporary, non-cache
data available across requests. The data is stored in one key/value collection
and expires automatically after a given timeframe.
  
The SessionBasedTempStore differs from the PrivateTempStore in that it can
store data based on the user session but without Drupal cookie session.
It means that you can use this storage to save data for an anonymous user
without breaking such things like Varnish and interact with them
after user authentication.
  
However, the issue with PrivateTempStore was brought up
here: [#2743931](https://www.drupal.org/project/drupal/issues/2743931)
Unfortunately, it forcibly creates a cookie session for anonymous user hence
it interferes with the static cache like Varnish.
  
In a contrast to PrivateTempStore, the SessionBasedTempStore creates a separate
cookie with the unique ID of the storage owner so that it can subsequently
interact with it to retrieve private data from the storage.
  
This module is actually a new generation
of [Session Cache API](https://www.drupal.org/project/session_cache)

## REQUIREMENTS

There are no special requirements.

## INSTALLATION

1) Enable the module session_based_temp_store
2) Call the service in your custom code.

This is an example of how you can use it:

    $temp_store_factory = \Drupal::service('session_based_temp_store');
    $temp_store = $temp_store_factory->get('my_module_name', 4800);

Here are the basic methods:

    $temp_store->set('key', 'value');
    $temp_store->get('key');
    $temp_store->delete('key');

Additionally this service has following public methods:

    // Retrieves an array of all values from the storage
    // for the current collection and owner.
    $temp_store->getAll();
    // Deletes all data from the storage for the current collection and owner.
    $temp_store->deleteAll();

## CONFIGURATION

Optionally you can override the $expire variable in your yml settings file.
This variable determines the storage lifetime.
By default it equals 604800 it's a 7 days.

## MORE INFORMATION

The supporting organization is [BUZZWOO!](https://www.drupal.org/buzzwoo)

## MAINTAINERS

[nortmas](https://www.drupal.org/u/nortmas)
