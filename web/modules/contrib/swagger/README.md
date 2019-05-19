# Swagger API Module
---

This module provides a [Swagger/OpenAPI](https://github.com/OAI/OpenAPI-Specification) compliant document describing the enabled REST resources of your site.

## Setup
```
drush -y en swagger swagger_json_schema
```

## Swagger Module Documentation
Enable a REST endpoint ([REST UI](https://www.drupal.org/project/restui) is helpful here), and go one of these endpoints, depending on what kind of resource you enabled.

* /swagger/openapi/entities?_format=json (for entity resources like users or content)
* /swagger/openapi/non-entity?_format=json (for other types of resources)


The Open API specification document in JSON format that describes all of the
entity REST resources can be downloaded from, `swagger/openapi/entities?_format=json`.

[Learn more about the Open API specification](https://github.com/OAI/OpenAPI-Specification)

## Swagger UI Module Documentation
The Swagger UI (swagger_ui) module provides a visual web UI for browsing REST API documentation. It makes use of the [swagger-ui library](https://github.com/swagger-api/swagger-ui).

Note: at the moment, only the 2.x version of swagger-ui library works with this module.

### Installation - Composer (recommended)
If you're using composer to manage the site (recommended), follow these steps:

1\. Run `composer require --prefer-dist composer/installers` to ensure that you have the `composer/installers` package installed. This package facilitates the installation of packages into directories other than `/vendor` (e.g. `/libraries`) using Composer.

2\. Add the following to the "repositories" section of your project's composer.json:
```
{
  "type": "package",
  "package":{
    "name": "swagger-api/swagger-ui",
    "version": "2.2.10",
    "type": "drupal-library",
    "dist"    : {
      "url": "https://github.com/swagger-api/swagger-ui/archive/v2.2.10.zip",
      "type": "zip"
    },
    "require": {
        "composer/installers": "~1.0"
    }
  }
}
```

3\. Add the following to the "installer-paths" section of `composer.json`:
    
```
"libraries/{$name}": ["type:drupal-library"],
```

4\. Run the following to add the swagger-ui library to your composer.json and download it to /libraries:
```
composer require swagger-api/swagger-ui 2.2.10
```

### Installation - Manual
Extract https://github.com/swagger-api/swagger-ui/archive/v2.2.10.zip into /libraries/swagger-ui

### Operation
To enable the Swagger UI module, run:

```
drush en -y swagger_ui
```
