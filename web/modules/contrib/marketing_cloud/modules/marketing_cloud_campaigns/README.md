MARKETING CLOUD CAMPAIGNS API
=============================


CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Service functions
 * Requirements
 * Installation
 * Configuration


INTRODUCTION
------------

This module enables the campaigns API in the Marketing Cloud as a service.

For details on individual API calls and the Marketing Cloud REST API, please
visit
https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/routes.htm


SERVICE FUNCTIONS
-----------------

| Name                              | Function                                    |
| --------------------------------- | ------------------------------------------- |
| Create campaign                   | createCampaign($json)                       |
| Get campaign collection           | getCampaignCollection($params)              |
| Get campaign                      | getCampaign($id)                            |
| Delete campaign                   | deleteCampaign($id)                         |
| Associate asset to campaign       | associateAssetToCampaign($id, $json)        |
| Get collection of campaign assets | getCollectionOfCampaignAssets($id)          |
| Get campaign asset                | getCampaignAsset($id, $assetId)             |
| Unassociate asset from campaign   | unassociateAssetFromCampaign($id, $assetId) |


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
