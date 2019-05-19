# Simple RESTful API in Drupal 8

This module provides a simple RESTful API in Drupal 8 that is populated by JSON files.

By default, the module provides routes for:
* A list of items - /api/simple/{DIRECTORY}
* A specific item - /api/simple/{DIRECTORY}/{ITEM_ID}

The item information is simply populated by a series of JSON files in public://{DIRECTORY}. 
Each JSON file should have the name {ITEM_ID}.json.

EXAMPLE - if you copy the "data" folder in the module to your public files directory, then the routes will be:
* /api/simple/data
* /api/simple/data/macbook_air_11
* /api/simple/data/macbook_air_13
