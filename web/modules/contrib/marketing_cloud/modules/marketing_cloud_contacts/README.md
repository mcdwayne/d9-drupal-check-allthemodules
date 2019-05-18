MARKETING CLOUD CONTACTS API
============================


CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Service functions
 * Requirements
 * Installation
 * Configuration


INTRODUCTION
------------

This module enables the contacts API in the Marketing Cloud as a service.

For details on individual API calls and the Marketing Cloud REST API, please
visit
https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/routes.htm


SERVICE FUNCTIONS
-----------------

| Name                                  | Function                                                 |
| ------------------------------------- | -------------------------------------------------------- |
| Get schemas collection                | getSchemasCollection()                                   |
| Create contacts                       | createContacts($json)                                    |
| Update contact                        | updateContacts($json)                                    |
| Create contact events                 | createContactEvents($json)                               |
| Remove contact from journey           | removeContactFromJourney($json)                          |
| Get contacts exit status from journey | getContactsExitStatusFromJourney($json)                  |
| Search contacts                       | searchContacts($json)                                    |
| Search attributes                     | searchAttributes($json)                                  |
| Insert attribute values by id         | insertAttributeValuesById($id, $json)                    |
| Update attribute values by id         | updateAttributeValuesById($id, $json)                    |
| Get contact key for email addresses   | getContactKeyForEmailAddresses($json)                    |
| Search attribute sets by name         | searchAttributeSetsByName($name)                         |
| Search attribute groups by schema     | searchAttributeGroupsBySchema($schemaId)                 |
| Get custom object info                | getCustomObjectInfo($id)                                 |
| Search attribute group id by schema   | searchAttributeGroupIdBySchema($schemaId, $id)           |
| Search attribute set definitions      | searchAttributeSetDefinitions($id)                       |
| Search attribute set names by schema  | searchAttributeSetNamesBySchema($schemaId, $name, $json) |
| Delete contacts by id                 | deleteContactsById($json)                                |
| Delete contacts by key                | deleteContactsByKey($json)                               |
| Delete contacts by list reference     | deleteContactsByListReference($json)                     |


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
