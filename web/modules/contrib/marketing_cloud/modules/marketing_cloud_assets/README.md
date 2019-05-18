MARKETING CLOUD ASSETS API
==========================


CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Service functions
 * Requirements
 * Installation
 * Configuration


INTRODUCTION
------------

This module enables the assets API in the Marketing Cloud as a service.

For details on individual API calls and the Marketing Cloud REST API, please
visit
https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/routes.htm


SERVICE FUNCTIONS
-----------------

| Name                  | Function                       |
| --------------------- | ------------------------------ |
| Simple query          | simpleQuery($params)           |
| Advanced query        | advancedQuery($json)           |
| Create category       | createCategory($json)          |
| Get categories        | getCategories($params)         |
| Get category by id    | getCategoryById($id)           |
| Update category by id | updateCategoryById($id, $json) |
| Delete category by id | deleteCategoryById($id)        |


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
