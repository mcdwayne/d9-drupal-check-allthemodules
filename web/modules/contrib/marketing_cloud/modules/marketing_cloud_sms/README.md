MARKETING CLOUD SMS API
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

This module enables the sms API in the Marketing Cloud as a service.

For details on individual API calls and the Marketing Cloud REST API, please
visit
https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/routes.htm


SERVICE FUNCTIONS
-----------------

| Name                                | Function                                                      |
| ----------------------------------- | ------------------------------------------------------------- |
| Create keyword                      | createKeyword($json)                                          |
| Queue Mo message                    | queueMoMessage($json)                                         |
| Create optin message                | createOptinMessage($json)                                     |
| Delete keyword by id                | deleteKeywordById($json)                                      |
| Post message to list                | postMessageToList($messageId, $json)                          |
| Import and send message             | importAndSendMessage($json)                                   |
| Get subscription status             | getSubscriptionStatus($json)                                  |
| Post message to number              | postMessageToNumber($messageId, $json)                        |
| Get tracking history of queued mo   | getTrackingHistoryOfQueuedMo($tokenId)                        |
| Queue contact import                | queueContactImport($listId, $json)                            |
| Refresh list                        | refreshList($listId)                                          |
| Delete keyword By long code         | deleteKeywordByLongCode($json)                                |
| Get delivery status of queued mo    | getDeliveryStatusOfQueuedMo($tokenId)                         |
| Get message list status             | getMessageListStatus($messageId, $tokenId)                    |
| Get import send status              | getImportSendStatus($tokenId)                                 |
| Create import send delivery report  | createImportSendDeliveryReport($tokenId, $json)               |
| Create message list delivery report | createMessageListDeliveryReport($tokenId, $messageID, $json)  |
| Get refresh list status             | getRefreshListStatus($listId, $tokenId)                       |
| Get import status                   | getImportStatus($listId, $tokenId)                            |
| Delete keyword by short code        | deleteKeywordByShortCode($json)                               |
| Get message contact status          | getMessageContactStatus($messageId, $tokenId)                 |
| Get message contact history         | getMessageContactHistory($messageId, $tokenId, $mobileNumber) |


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
