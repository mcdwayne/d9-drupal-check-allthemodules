# Waterwheel Module

---

This module provides a [Swagger/OpenAPI](https://github.com/OAI/OpenAPI-Specification) compliant document describing the enabled REST resources of your site.

If you desire to use Drupal core rest functionality with [Waterwheel.js](https://github.com/acquia/waterwheel.js), then this module is required to enable the [population of resources](https://github.com/acquia/waterwheel.js#populate-waterwheel-resources).

## Setup

```
drush dl waterwheel
```

```
drush -y en waterwheel waterwheel_json_schema
```

## Documentation

The Open API specification document in JSON format that describes all of the
entity REST resources can be downloaded from, `waterwheel/openapi/entities?_format=json`.

[Learn more about the Open API specification](https://github.com/OAI/OpenAPI-Specification)


## Using within a Drupal site

If you would like to access the Waterwheel.js library from Javascript on your Drupal site:

1. Down the latest built version of the `waterwheel.js` file from the [releases page](https://github.com/acquia/waterwheel-js/releases).
2. Place the `waterwheel.js` file into the root `/libraries` folder.
3. The file should be at `[DRUPAL_ROOT]/libraries/waterwheel/waterwheel.js`
4. If you have already enabled the module clear your caches, `drush cr`.
5. Include the library like this,

```php
$element['#attached']['library'][] = 'waterwheel/waterwheel';
```
