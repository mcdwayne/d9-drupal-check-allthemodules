GeoIP
-----

The GeoIP module provides GeoLocator plugins that can be used to geolocate site visitors. By default the module provides **Local** and **CDN** based geolocation plugins.

# Features

## Local

The **Local** geolocation plugin utilizes the Maxmind GeoIP2 databases. The plugin supports both the Country and City databases. However, it only checks for the country currently.

We require the geoip2/geoip2 library for interacting with the Maxmind database.

## CDN

CDNs use their own geolocation services and often forward the information via an HTTP header.

Supported CDNs
* Cloudflare
* Amazon CloudFront

Soon there will be a "Custom header" setting for highly configurable CDNs like Fastly.

# Installation

## GeoIP

````
cd /path/to/drupal
composer require drupal/goeip

drupal module:install geoip
````

## Maxmind Libraries

Visit http://dev.maxmind.com/geoip/geoip2/geolite2/#Downloads and download either the City (large ~15MB) or the Country database.

Place the extracted **.mmdb** file in the public files directory (i.e, sites/default/files)

