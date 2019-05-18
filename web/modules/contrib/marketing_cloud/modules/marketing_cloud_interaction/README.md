MARKETING CLOUD INTERACTION API
=======================


CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Service functions
 * Requirements
 * Installation
 * Configuration


INTRODUCTION
------------

This module enables the interaction API in the Marketing Cloud as a service.

For details on individual API calls and the Marketing Cloud REST API, please
visit
https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/routes.htm


SERVICE FUNCTIONS
-----------------

| Name                             | Function                                         |
| -------------------------------- | ------------------------------------------------ |
| Retrieve rest discovery document | retrieveRestDiscoveryDocument()                  |
| Insert journey                   | insertJourney($json)                             |
| Search journeys                  | searchJourneys($params, $json)                   |
| Update journey version           | updateJourneyVersion($json)                      |
| Create event definition          | createEventDefinition($json)                     |
| Fire event                       | fireEvent($json                                  |
| Get journey                      | getJourney($id)                                  |
| Delete journey                   | deleteJourney($id, $versionNumber)               |
| Get journey audit log            | getJourneyAuditLog($id, $action, $versionNumber) |
| Get publish status               | getPublishStatus($statusId)                      |
| Stop journey                     | stopJourney($id, $versionNumber)                 |
| Publish journey version          | publishJourneyVersion($id, $versionNumber)       |


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
