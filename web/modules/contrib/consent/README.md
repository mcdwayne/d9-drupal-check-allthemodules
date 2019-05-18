# Consent

Let users submit their consent.

## Module features

- A flexible and configurable consent layer powered by OIL.
- An optional backend for storing user consents.
- An optional submodule for delivering the consent layer via iFrames,
  such as on Accelerated Mobile Pages (AMP).

## About OIL

This module uses and includes the OIL framework by Axel Springer SE.
The OIL framework is an Open Source GPDR Consent Management Provider (CMP),
Opt-In Layer (oil.js) and supports the IAB Framework.

The included OIL.js files are from http://oil.axelspringer.com/release/.
This module does not contain all files from the RELEASE package:
- The docs folder was removed to save space and not to be accessed publicly.
- All files regards the "Power Opt-In" (POI) feature was removed.
  In case you want to use your site as a Power Opt-In hub,
  you'll need to host the hub files on your own.

More about the OIL framework:
- Main website: https://www.oiljs.org/
- Repository with source code: https://github.com/as-ideas/oil
- GPLv2 licensed: https://github.com/as-ideas/oil/blob/master/LICENSE
- Contains several MIT-licensed third party assets which are listed at
  https://github.com/as-ideas/oil/blob/master/package.json as dependencies.

## Requirements

This module requires no modules outside of Drupal core.

## Installation and configuration

Install this module as you would normally install a contributed Drupal module.
Visit https://www.drupal.org/node/1897420 for further information.

Once installed, you can place a block "Consent Layer" into your
block layout via /admin/structure/block. The block can be placed into
the main content region, but it can also be placed into any other region.

The block offers to setup configuration parameters for the OIL framework,
which are documented at
https://oil.axelspringer.com/docs/#functional-configuration-parameters.

On every page where the block is enabled, the consent layer will be shown,
when users have not opted-in their consent yet.
