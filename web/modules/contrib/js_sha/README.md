# jsSHA

Provides Drupal integration with the jsSHA, a JavaScript implementation of the
complete Secure Hash Standard family as well as HMAC.


## Installation

This module requires the jsSHA library from https://github.com/Caligatio/jsSHA.

1. Download the jsSHA plugin from https://github.com/Caligatio/jsSHA
2. Place the plugin folder in the root libraries folder (/libraries/jsSHA).
3. Enable jsSHA in the Drupal admin.


## Basic Usage

There is no configuration. See https://github.com/Caligatio/jsSHA for details.


## Advanced Usage

To install the jsSHA library with Composer, make the below additions to your
project's root composer.json file.

1. Add a `repositories` entry for the `js_sha` library.

```
"repositories": {
  "js_sha": {
    "type": "package",
    "package": {
      "name": "js_sha/js_sha",
      "version": "v2.3.1",
      "type": "drupal-library",
      "extra": {
        "installer-name": "jsSHA"
      },
      "dist": {
        "url": "https://github.com/Caligatio/jsSHA/archive/v2.3.1.zip",
        "type": "zip"
      },
      "require": {
        "composer/installers": "~1.0"
      }
    }
  }
}
```

2. Require the `js_sha` library for your project.

```
"require": {
  "js_sha/js_sha": "v2.3.1"
}
```
