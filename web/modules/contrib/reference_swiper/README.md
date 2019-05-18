# Reference swiper

## Description

This module integrates the Swiper JS library (http://idangero.us/swiper) with
drupal's entity reference fields by providing a new formatter for entity
reference fields.

Please read the following description of the Swiper library before using this
module:

"Swiper - is the free and most modern mobile touch slider with hardware
accelerated transitions and amazing native behavior. It is intended to be used
in mobile websites, mobile web apps, and mobile native/hybrid apps. Designed
mostly for iOS, but also works great on latest Android, Windows Phone 8 and
modern Desktop browsers

Swiper is not compatible with all platforms, it is a modern touch slider which
is focused only on modern apps/platforms to bring the best experience and
simplicity.".

Please note that this modules **supports only Swiper v. 3** at this point as
the library evolved rapidly and I can't put enough time into maintaining this
module at the moment.

## Features

- Format any multivalue (content) entity reference field as a Swiper slider, for
  example, use file or media entities to create image sliders, or just apply it
  to multiple node references to slide through them.
- Swiper JS supports sliders on touch devices.
- Make use of the many configuration options available in Swiper's API, by just
  simply creating a Swiper option set on the provided admin UI.
- Swiper option sets are config entities, so you may import and export them, or
  even edit them with drush.
- Developers may access each field's Swiper instance separately in JS.

## Installation and configuration

1. Follow the steps under `Adding the library via composer` below,
or do the following: Download the last release of the Swiper library in the
Swiper3 branch from here:
https://github.com/nolimits4web/swiper/archive/Swiper3.zip . Then unzip it
into `DRUPAL_ROOT/libraries` and rename the `swiper-Swiper3` folder to
`swiper` such that the file `swiper.jquery.min.js` is accessible at 
`DRUPAL_ROOT/libraries/swiper/dist/js/swiper.jquery.min.js` .
2. Enable the module as usual using the UI, drush or drupal console.
3. Add a Swiper option set by navigating to
`/admin/config/system/reference-swiper` .
Refer to http://idangero.us/swiper/api for further information.
4. Navigate to the `Manage display` tab of your entity or bundle and switch the
   formatter of your entity reference field to "Reference Swiper".
5. Edit the formatter settings and select the option set created in step 3 by
   entering its name in the autocomplete field.

### Adding the library via composer

If you want to add the Swiper library via composer you can do so by following
these steps:

- You have https://asset-packagist.org repository to your root `composer.json`
file:

```javascript
  "repositories": [
    ...
    {
      "type": "composer",
      "url": "https://asset-packagist.org"
    },
```

- Make sure npm-assets are installed in the `DRUPAL_ROOT/libraries` folder:

```javascript
  "extra": {
    ...
    "installer-types": [
      ...
      "npm-asset"
    ],
    "installer-paths": {
      ...
      "web/libraries/{$name}": [
        "type:npm-asset"
      ]
    ]
  }
```

- Requiring the following packages:

   ```javascript
   "require":
     ...
     "oomphinc/composer-installers-extender": "^1.1",
     "composer/installers": "^1.2",
     "npm-asset/swiper": "^3.0"
   ```

## Known issues

1. Floating a field that is using the Reference Swiper formatter with CSS will
   break the Swiper.
2. The following Swiper features arent't supported yet:
- parallax
- lazy loading
- zoom
- other new parameters that were added in version 3.4
3. The module works only with the Swiper3 branch of the Swiper library.

Feel free to create an issue at
http://drupal.org/project/issues/reference_swiper in case you find a bug/have a
feature request.

## Credits:

Current maintainers:

- Sebastian Leu - https://www.drupal.org/u/s_leu
