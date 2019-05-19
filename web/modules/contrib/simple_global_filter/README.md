# Simple Global Filter

This module provides an easy way to add global filters, based on taxonomies, in order to filter
content on your side.

# Features
* Create a global filter and display it in the site using a block
* Use the global filters for setting up a block visibility (via Conditions plugins)
* Integrate it with Views
* Change the filter globally using GET parameters

# Installation
This module depends on session_cache contrib module, and, at the time of writing this, it
needs to be patched. So follow these instructions for properly installing and patching
session_cache, and then installing this module:
- Add this patch in your composer.json file, inside the "extra" section:
"patches": {
          "drupal/session_cache": {
            "#2957370: Drupal 8 Release?": "https://www.drupal.org/files/issues/2018-08-24/session_cache-d8port-2957370-3.patch"
          }
        }
- Install the latest dev version of session_cache. The previous patch will be applied:
`composer require drupal/session_cache:1.x-dev#fddb53d4c14b2badc3f016678b85733829382e94`
- Install this module:
`composer require drupal/simple_global_filter`

# Configuration
* Create a global filter in /admin/structure/global_filter. Global filters are based on Taxonomy
  vocabularies, so you need to have at least one vocabulary created.
  Also, add the default value, which will be used when the user has not (yet) chosen any option.
* Once the global filter is created, a block will be created after it. Place it wherever you want.
* Also,a new condition will be created for each global filter. You can use this feature in any
  element that support plugin conditions, such as a block' visibility settings.
* Also, new filter options are available in Views. For using this feature, configure an entity that uses
  a field which is a reference to a term, which is under a global filter. So, for example, create
  a Vocabulary called Countries and add some countries. Then, in a content type, create a field
  that references to this Country vocabulary. And then create a view which lists the same
  content type and try to filter by countries. In the filter panel, select 'Global' group
  and you should see there the filters provided by this module.
* If you want to use the global filters in your module, invoke the 'simple_global_filter.global_filter' service.
* If you want to modify the global filter via a GET parameter, this module supports it. Just add the global
  filter machine name as a GET parameter. So, for example, if you created a filter with machine name
  'country', and you have a country taxonomy term with id 35, if you want to filter automatically
  by this value, use it like this: example.com?country=35
* If you don't want the term ids to appear in the URL, and instead you would like to configure
  which text should appear, you can do it by setting term's alias. First, create a field  of
  type 'Text (Plain)' and attach it to the Taxonomy you use as base for the global filter.
  When configuring the global filter, select the option 'Use alias value' and choose the field
  you just created.
  Finally, edit the term and set the alias value in the field. This will appear in the URL
  when submitting the global filter.

# Dependencies
This module depends on session_cache module. At the time of writting this, the following patch 
needs to be applied:
https://www.drupal.org/files/issues/2018-08-24/session_cache-d8port-2957370-3.patch

# Author: Alberto G. Viu (alberto@exove.fi) for Exove Ltd.
