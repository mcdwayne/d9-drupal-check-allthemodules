MARKETING CLOUD PUSH API
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

This module enables the push API in the Marketing Cloud as a service.

For details on individual API calls and the Marketing Cloud REST API, please
visit
https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/routes.htm


SERVICE FUNCTIONS
-----------------

| Name                                    | Function                                                |
| --------------------------------------- | ------------------------------------------------------- |
| Create push message                     | createPushMessage($json)                                |
| Get push messages                       | getPushMessages()                                       |
| Create location                         | createLocation($json)                                   |
| Get locations                           | getLocations()                                          |
| Get app info                            | getAppInfo($appId)                                      |
| Update push message                     | updatePushMessage($messageId, $json)                    |
| Delete push message                     | deletePushMessage($messageId)                           |
| Get push message                        | getPushMessage($messageId)                              |
| Get specific location                   | getSpecificLocation($locationId)                        |
| Update location                         | updateLocation($locationId, $json)                      |
| Delete location                         | deleteLocation($locationId)                             |
| Get custom keys                         | getCustomKeys($appId)                                   |
| Update custom keys                      | updateCustomKeys($appId, $json)                         |
| Delete custom keys                      | deleteCustomKeys($appId)                                |
| Refresh list                            | refreshList($id)                                        |
| Send message to all                     | sendMessageToAll($messageId, $json)                     |
| Send message to tagged users            | sendMessageToTaggedUsers($messageId, $json)             |
| Send message to list                    | sendMessageToList($messageId, $json)                    |
| Update custom key                       | updateCustomKey($appId, $key)                           |
| Delete custom key                       | deleteCustomKey($appId, $key)                           |
| Send message to mobile devices in batch | sendMessageToMobileDevicesInBatch($messageId, $json)    |
| Send message to mobile devices          | sendMessageToMobileDevices($messageId, $json)           |
| Get refresh list status                 | getRefreshListStatus($id, $tokenId)                     |
| Get delivery status of message app      | getDeliveryStatusOfMessageApp($messageId, $tokenId)     |
| Get delivery status of message tag      | getDeliveryStatusOfMessageTag($messageId, $tokenId)     |
| Get delivery status of message list     | getDeliveryStatusOfMessageList($messageId, $tokenId)    |
| Get delivery status of message contact  | getDeliveryStatusOfMessageContact($messageId, $tokenId) |


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
