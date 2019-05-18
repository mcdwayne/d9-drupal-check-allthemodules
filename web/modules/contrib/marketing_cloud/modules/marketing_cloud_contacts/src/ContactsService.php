<?php

namespace Drupal\marketing_cloud_contacts;

use Drupal\marketing_cloud\MarketingCloudService;

/**
 * Class ContactsService.
 *
 * For all of the API service calls, a correct JSON data payload is expected.
 * This is then validated against the JSON Schema. This approach minimises
 * any short-term issues with changes in the SF API, provides a sanitized
 * interface to send API calls and leaves flexibility for any modules that
 * want to use this as a base-module.
 *
 * @package Drupal\marketing_cloud
 */
class ContactsService extends MarketingCloudService {

  private $moduleName = 'marketing_cloud_contacts';

  /**
   * Retrieves the collection of all contact data schemas contained in the current account.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/schemasCollection.htm
   */
  public function getSchemasCollection() {
    $machineName = 'get_schemas_collection';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass());
  }

  /**
   * Creates a new contact with the specified information in the specified attribute groups.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/createContacts.htm
   */
  public function createContacts($json) {
    $machineName = 'create_contacts';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Updates contacts with the specified information in the specified attribute groups.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/updateContacts.htm
   */
  public function updateContacts($json) {
    $machineName = 'update_contacts';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Posts information from an event and associates that information with a contact.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/contactEvents.htm
   */
  public function createContactEvents($json) {
    $machineName = 'create_contact_events';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Removes a contact from a journey or from one or more versions of a journey.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/contactExitRequest.htm
   */
  public function removeContactFromJourney($json) {
    $machineName = 'remove_contact_from_journey';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Returns the status of a request to remove a contact from a journey or specified versions of a given journey.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/contactExitStatus.htm
   */
  public function getContactsExitStatusFromJourney($json) {
    $machineName = 'get_contacts_exit_status_from_journey';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Finds specified contacts based on provided attributes.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/searchSchema.htm
   */
  public function searchContacts($json) {
    $machineName = 'search_contacts';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Searches for attributes represented anywhere in your data model.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/fetchAttributes.htm
   */
  public function searchAttributes($json) {
    $machineName = 'search_attributes';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Inserts a collection of attribute value containers or the data rows of a specified attribute set by ID or name.
   *
   * @param string $id
   *   The ID of the attribute set expressed in the form of a GUID (UUID).
   *   Required if not using key or name.
   *   or
   *   The name of the attribute set. Prefix the parameter with name:. For
   *   example, /attributeSets/name:{name}. Required if not using ID.
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/attributeSetsDataIDInsert.htm
   */
  public function insertAttributeValuesById($id, $json) {
    $machineName = 'insert_attribute_values_by_id';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[id]' => $id]);
  }

  /**
   * Updates a collection of attribute value containers or the data rows of a specified attribute set by ID or name.
   *
   * @param string $id
   *   The ID of the attribute set expressed in the form of a GUID (UUID).
   *   Required if not using key or name.
   *   or
   *   The name of the attribute set. Prefix the parameter with name:. For
   *   example, /attributeSets/name:{name}. Required if not using ID.
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/attributeSetsDataIDUpdate.htm
   */
  public function updateAttributeValuesById($id, $json) {
    $machineName = 'update_attribute_values_by_id';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[id]' => $id]);
  }

  /**
   * Retrieves the contact key for one or more email channel addresses.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/retrieveContactKey.htm
   */
  public function getContactKeyForEmailAddresses($json) {
    $machineName = 'get_contact_key_for_email_addresses';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Retrieves a collection of attribute value containers or the data rows of a specified attribute set by name.
   *
   * @param string $name
   *   The name of the attribute set. Prefix the parameter with name:. For
   *   example, /attributeSets/name:{name}.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/attributeSetsDataName.htm
   */
  public function searchAttributeSetsByName($name) {
    $machineName = 'search_attribute_sets_by_name';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[name]' => $name]);
  }

  /**
   * Retrieves all attribute groups associated with a specified contact data schema.
   *
   * @param string $schemaId
   *   The ID of the schema.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/retrieveAttributeGroups.htm
   */
  public function searchAttributeGroupsBySchema($schemaId) {
    $machineName = 'search_attribute_groups_by_schema';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[schemaId]' => $schemaId]);
  }

  /**
   * Retrieves information on whether the contact model for an account uses the custom object.
   *
   * @param string $id
   *   ID of the custom object as GUID value.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getCustomObject.htm
   */
  public function getCustomObjectInfo($id) {
    $machineName = 'get_custom_object_info';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id]);
  }

  /**
   * Retrieves a specified attribute group (by ID, key, or name) associated with a specified contact data schema.
   *
   * @param string $schemaId
   *   The ID of the schema.
   * @param string $id
   *   The ID of the attribute group expressed in the form of a GUID (UUID).
   *   Required if not using key or name.
   *   or
   *   Key of the attribute group. Prefix the parameter with key:. For example,
   *   /attributeGroups/key:{key}. Required if not using ID or name.
   *   or
   *   Name of the attribute group. Prefix the parameter with name:. For
   *   example, /attributeGroups/name:{name}. Required if not using ID or key.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/retrieveAttributeGroupsID.htm
   */
  public function searchAttributeGroupIdBySchema($schemaId, $id) {
    $machineName = 'search_attribute_group_id_by_schema';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[schemaId]' => $schemaId, '[id]' => $id]);
  }

  /**
   * Retrieves all attribute set definitiond in your contact data model.
   *
   * @param string $id
   *   The ID of the attribute set definition expressed in the form of a GUID
   *   (UUID). Leave off to retrieve all attribute set definitions in your
   *   contact data model.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/retrieveAttributeSetDefinitions.htm
   */
  public function searchAttributeSetDefinitions($id) {
    $machineName = 'search_attribute_set_definitions';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id]);
  }

  /**
   * Searches for a collection of attribute value containers or the data row of a specified attribute set (by name) within a specified schema.
   *
   * @param string $schemaId
   *   The ID of the schema.
   * @param string $name
   *   The name of the attribute set.
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/searchAttributeSetsDataName.htm
   */
  public function searchAttributeSetNamesBySchema($schemaId, $name, $json) {
    $machineName = 'search_attribute_set_names_by_schema';
    return $this->apiCall($this->moduleName, $machineName, $json, ['[schemaId]' => $schemaId, '[name]' => $name]);
  }

  /**
   * Deletes contacts based on specified contact ID values.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/DeleteByContactIDs.htm
   */
  public function deleteContactsById($json) {
    $machineName = 'delete_contacts_by_id';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Deletes contacts based on specified contact key values.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/DeleteByContactKeys.htm
   */
  public function deleteContactsByKey($json) {
    $machineName = 'delete_contacts_by_key';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Deletes contacts based on specified list reference value.
   *
   * @param array|object|string $json
   *   The JSON payload.
   *
   * @return array|bool|null
   *   Result of the API call or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/DeleteByListReference.htm
   */
  public function deleteContactsByListReference($json) {
    $machineName = 'delete_contacts_by_list_reference';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

}
