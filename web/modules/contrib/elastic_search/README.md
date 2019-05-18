# Elastic Search
[ ![Codeship Status for ibrows/elastic_search](https://app.codeship.com/projects/3ca0ef00-22a3-0135-eb40-52028c1190b7/status?branch=master)](https://app.codeship.com/projects/221747)
[ ![Codacy Badge](https://api.codacy.com/project/badge/Grade/d3396f1fe8104eac9a376952d37506a9)](https://www.codacy.com?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=ibrows/elastic_search&amp;utm_campaign=Badge_Grade)


INTRODUCTION
------------

The elastic_search module attempts to put elastic search paradigms at the heart of drupal searching. Although [Search API](https://www.drupal.org/project/search_api) is great
it is heavily built around Solr concepts and as such it does not translate directly to the way elastic search works. This is an attempt to bring elastic search ideas directly in to drupal.

This means:

    * Directly configure and push elastic search mappings from your drupal instance
    * Full mapping of entity fields to elastic search field types. With all current elastic search options available
    * Uses the elasticsearch/elasticsearch libary
    * Module controls the indexes for you, to fully exploit  multilingual analysers and optimize document structure
    * Entity references are converted to inline documents (up to depth X) for full searching capability
    * Content types can be excluded from the index, or made to be mapped as inner documents / children only
    * Easy to extend type mappings to your custom fields by implementing an event subscriber
    * Easy to add field data normalization via a plugin

This module is only for mapping and indexing of content with elastic search. You will need additional modules to display result content, such as the soon to be published
[Elastic Search View](https://github.com/ibrows/elastic_search_view) .
This module could also be integrated with [Elastic Search Helper](https://www.drupal.org/project/elasticsearch_helper) as it decouples the mapping and indexing of documents from their retrieval.


REQUIREMENTS
------------

* PHP >=7.0
* Elastic Search >= 5.0.0
* Ace code editor. Add this to your libraries/ folder so that /libraries/ace/src-min-noconflict/ace.js is available
* Cron


INSTALLATION
------------

* Install as you would [normally install a contributed Drupal 8 module](https://drupal.org/documentation/install/modules-themes/modules-8) .
* Install [Ace Code Editor](https://ace.c9.io/) to your libraries/ folder so that libraries/ace/src-min-noconflict/ace.js is available


CONFIGURATION
-------------

See the /docs folder for the full git book on configuring this module.
Or see https://ibrows.github.io/elastic_search/


Related Modules
---------------

[Elastic Search Views](http://drupal.org/project/elastic_search_views)
Use elastic search to provide views results data, can be rendered from local db or display elastic json response directly for decoupled FE designs


NOTES
-----

Index documents in bulk and update indexes and mappings in small groups. This seems to produce the best results for cluster memory and to reduce potential timeouts
By default index and mapping operations are done 1 at a time and documents are indexed in batches of 100. These settings can be changed on the server config page
Because batches involve writing to the database you need to be careful that the total number does not cause an insert query which is too large. 100-200 is optimal

Debug logging in elasticsearch uses print_r and the drupal log reports pages dont want to show this. If you need to debug with this you are best looking directly at the watchdog table or using drupal console


TESTING
-------

elastic_search uses [Mockery](http://docs.mockery.io) for test mocking because of a bug in the phpunit version that drupal requires which prevents some classes from being mocked.
All elastic_search features are tested using [Drupal Module Tester](https://github.com/ibrows/drupal_module_tester) and codeship.


DEVELOPMENT
-----------

The dev folder contains a complete docker environment that can be used to develop this module.
See the /docs folder for the full git book on developing and extending this module.

We use Github and a gitflow pull request workflow.
Each pull request should have a related and cross-linked drupal.org issue.

For the testing to run correctly the following branch naming patterns should be observed:

- Features should be implemented in a branch named feature/{my_feature_name}
- Issues should be implemented in a branch named issue/{my_issue_id}

PR's will not be accepted without passing tests!

https://github.com/ibrows/elastic_search


CONTRIBUTIONS
-------------

We welcome contributions in the following areas

* Comment Mapping
* Custom Analyzer rendering in maps
* Testing for forms
* Custom Field Mappers for 3rd party modules

Please do not submit code style or readme punctuation updates that add no value to the plugin.


MAINTAINERS
-----------

Current Maintainers:

* Alessio De Francesco [(aless_io)](https://www.drupal.org/u/aless_io)

Past contributors:

* Tom Whiston [(Tom.W)](https://www.drupal.org/u/tomw-0)

This project has been sponsored by:

* [PwC's Experience Center Zurich](http://digital.pwc.ch/en/)
