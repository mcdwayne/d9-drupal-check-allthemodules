MARKETING CLOUD DATA EVENTS API
===============================


CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Service functions
 * Requirements
 * Installation
 * Configuration


INTRODUCTION
------------

This module enables the data events API in the Marketing Cloud as a service.

For details on individual API calls and the Marketing Cloud REST API, please
visit
https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/routes.htm


SERVICE FUNCTIONS
-----------------

| Name                                         | Function                                                                   |
| -------------------------------------------- | -------------------------------------------------------------------------- |
| Insert data extension rows by key            | insertDataExtensionRowsByKey($key, $json)                                  |
| Insert data extension row by key             | insertDataExtensionRowByKey($key, $primaryKeys, $json)                     |
| Increment column value by data extension key | incrementColumnValueByDataExtensionKey($key, $primaryKeys, $column, $step) |


REQUIREMENTS
------------

 * marketing_cloud


INSTALLATION & CONFIGURATION
----------------------------

This module will add a tab to admin > config > marketing cloud. here you can
edit the individual rest call definitions.

Please see the
[community documentation pages](https://www.drupal.org/docs/8/modules/marketing-cloud)
for information on installation and configuration.
