CONTENTS OF THIS FILE
---------------------
   
 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Troubleshooting
 * FAQ
 * Maintainers
 

INTRODUCTION
---------------------

This module provides a block having textfield with autocomplete suggestions 
from the Google Places API. Integrate it with geolocation in order to use it 
with geolocation proximity search.

 For a full description of the module, visit the project page:
   https://www.drupal.org/project/google_places_search_form
   
 To submit bug reports and feature suggestions, or to track changes:
   https://www.drupal.org/project/issues/google_places_search_form


REQUIREMENTS
---------------------

Geolocation module.
https://www.drupal.org/project/geolocation


INSTALLATION
---------------------

Install as you would  normally install a Druapl contributed module. Visit:
https://www.drupal.org/docs/8/extending-drupal-8/installing-drupal-8-modules
for further informtaion.


CONFIGURATION
---------------------

Go to Administration » Configuration » Development » Google Places Form:
  * Enter your google places api key.
  * Enter the page path for proximity search (path of the view having proximity
   search in contextual filters).
  * Choose if you want to show distance field or not(Optional).
  * Choose the distance parameter i.e., Km or Miles (Optional).


How to get a Google API key
---------------------------

Go to https://code.google.com/apis/console/
* Create a new project
* Enable Google Places API 
(Go to https://console.developers.google.com/apis/library?project=YOUR_PROJECT).
Be warned that skipping this step will make the request to the Google Places Api
 fail with 500 code.
* Go to Credentials
* Create New Key
* Select Server key
* Copy the API key.
* Paste it in the module configuration page, at 
'/admin/config/google-places-search'


HOW TO USE
---------------------

* Go to Administration » Structure » Block Layout.
* Click on Place Block button (For respective region).
* Search for 'Google Places Search Block' and place it in respective the region.
* Type in the autocomplete textfield and you will get suggestions from Google 
Places API.


MAINTAINERS
---------------------

Current maintainers:
  * Akansha Saxena : (https://www.drupal.org/u/saxenaakansha30)
