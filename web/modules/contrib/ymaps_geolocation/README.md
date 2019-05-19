# Ymaps Geolocation field formatter

CONTENTS OF THIS FILE
---------------------
* Introduction
* Requirements
* Installation
* Configuration

INTRODUCTION
------------
Yandex geolocation field formatter is a simple module for view 
Geolocation Field as Yandex map. 

Geolocation Field provides a field type to store geographical locations as pairs 
of latitude and longitude (lat, lng). 

This module tested with 8.x-1.11 version of geolocation field module. After 
installation you can find  "Geolocation Yandex map" formatter for display 
and edit Geolocation field.

REQUIREMENTS
------------
This module requires the following modules:

* Geolocation (https://www.drupal.org/project/geolocation)

INSTALLATION
------------

* Install https://www.drupal.org/project/geolocation
* Install this module as usual

CONFIGURATION
-------------

* Setup Yandex API key (https://tech.yandex.ru/maps/keys/) 
  on admin page /admin/config/content/ymaps_geolocation
* Create Geolocation field 
* Select for created field display type Geolocation Yandex map
* Select for created field display form type Geolocation Yandex map.
