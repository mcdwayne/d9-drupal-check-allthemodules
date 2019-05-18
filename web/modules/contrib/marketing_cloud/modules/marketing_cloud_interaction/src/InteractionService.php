<?php

namespace Drupal\marketing_cloud_interaction;

use Drupal\marketing_cloud\MarketingCloudService;

/**
 * Class InteractionService.
 *
 * For all of the API service calls, a correct JSON data payload is expected.
 * This is then validated against the JSON Schema. This approach minimises
 * any short-term issues with changes in the SF API, provides a sanitized
 * interface to send API calls and leaves flexibility for any modules that
 * want to use this as a base-module.
 *
 * @package Drupal\marketing_cloud
 */
class InteractionService extends MarketingCloudService {

  private $moduleName = 'marketing_cloud_interaction';

  /**
   * Retrieves the discovery document for the collection of journey resources. To call this resource, assign your API Integration the Automation | Interactions | Read scope.
   *
   * @return array|bool|null
   *   The result of the API call, or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/restDiscovery.htm
   */
  public function retrieveRestDiscoveryDocument() {
    $machineName = 'retrieve_rest_discovery_document';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass());
  }

  /**
   * Creates or saves a journey. To create a new journey provide the request body in the appropriate Journey Specification. Please read the Journey Spec page to understand which properties are required to create a journey via the API. The id, key, createdDate, modifiedDate, status and definitionId are assigned by Journey Builder and are never to be passed in as parameters for creating a journey. To call this resource, assign your API Integration the Automation | Interactions | Read scope.
   *
   * @param array|object|string $json
   *   The JSON body payload.
   *
   * @return array|bool|null
   *   The result of the API call, or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/postCreateInteraction.htm
   */
  public function insertJourney($json) {
    $machineName = 'insert_journey';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Retrieves a collection of all journeys. The journey collection resources are embedded in the items property of the response body. Use both a sort order and paging to minimize the response size and response time. To call this resource, assign your API Integration the Automation | Interactions | Read scope.
   *
   * @param array $params
   *   URL filter params. Possible key/values:
   *     status string A status description upon which to filter journeys. Can
   *       be one of: Draft, Published, ScheduledToPublish, Stopped,
   *       Unpublished, Deleted. The default value is blank, which returns all
   *       statuses.
   *     versionNumber number Version number of the journey to retrieve. The
   *       default value is published version or latest available version
   *       number which meets other search criteria.
   *     specificApiVersionNumber number Version number of the
   *       workflowApiVersion upon which to filter journeys. The default value
   *       is 1.
   *     mostRecentVersionOnly boolean A flag to indicate whether to fetch only
   *       the most recent version of matching journeys. The default value is
   *       true.
   *     nameOrDescription string A search string inside the journey's name or
   *       description properties upon which to match for filtering.
   *     extras string A list of additional data to fetch. Available values
   *       are: all, activities, outcome and stats. The default value is blank,
   *       which returns all extras.
   *     orderBy string Specify how to order the journeys. Valid ordering
   *       columns are: ModifiedDate (default), Name, Performance. Valid values
   *       are: DESC, ASC. The default value is 'ModifiedDate DESC'.
   *     tag string Specify a single tag to filter results to only include
   *       journeys associated with that tag.
   *     $page number The number of pages to retrieve. The default value is 1.
   *     $pageSize number The number of results to return on a page. The
   *       default and maximum is 50.
   * @param array|object|string $json
   *   The JSON body payload.
   *
   * @return array|bool|null
   *   The result of the API call, or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getInteractionCollection.htm
   */
  public function searchJourneys(array $params = [], $json = NULL) {
    $machineName = 'search_journeys';
    $json = empty($json) ? new \stdClass() : $json;
    return $this->apiCall($this->moduleName, $machineName, $json, [], $params);
  }

  /**
   * Updates a journey version. To call this resource, assign your API Integration the Automation | Interactions | Read scope.
   *
   * @param array|object|string $json
   *   The JSON body payload.
   *
   * @return array|bool|null
   *   The result of the API call, or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/putUpdateInteraction.htm
   */
  public function updateJourneyVersion($json) {
    $machineName = 'update_journey_version';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Creates an event definition (name and data schema for an event) and defines an event definition key. The resource uses this key when firing an event to send it to the appropriate journey. Typically, marketers create the event definition in the Journey Builder UI. Use this resource instead if you are using a custom application for Journey Builder functionality. To call this resource, assign your API Integration the Automation | Interactions | Read scope.
   *
   * @param array|object|string $json
   *   The JSON body payload.
   *
   * @return array|bool|null
   *   The result of the API call, or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/createEventDefinition.htm
   */
  public function createEventDefinition($json) {
    $machineName = 'create_event_definition';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Fires the entry event that initiates the journey.
   *
   * @param array|object|string $json
   *   The JSON body payload.
   *
   * @return array|bool|null
   *   The result of the API call, or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/postEvent.htm
   */
  public function fireEvent($json) {
    $machineName = 'fire_event';
    return $this->apiCall($this->moduleName, $machineName, $json);
  }

  /**
   * Deletes a journey by ID or key. When deleting last version of a journey, check if the journey is associated with a tag and unassociated the tag using the deleteTags resource.
   *
   * @param string $id
   *   ID of the journey in the form of a GUID (UUID). Required if not using a
   *   key.
   *   or
   *   The key of the journey. Required if not using ID. Prefix the parameter
   *   with key:. For example, /interactions/key:{key}.
   * @param array $params
   *   Array of extra URI params. Acceptable key/values:
   *     versionNumber number Required Version number of the journey to
   *       retrieve.
   *     extras string A list of additional data to fetch. Available values
   *       are: all, activities, outcomes and stats. Default is ''.
   *
   * @return array|bool|null
   *   The result of the API call, or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getInteractionById.htm
   */
  public function getJourney($id, array $params = []) {
    $machineName = 'get_journey';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id], $params);
  }

  /**
   * Retrieves a single journey by ID or key. To call this resource, assign your API Integration the Automation | Interactions | Read scope.
   *
   * @param string $id
   *   The ID of the journey to delete expressed in the form of a GUID (UUID).
   *   Required if not using a key. The ID deletes all versions of the journey,
   *   unless a versionNumber is provided.
   *   or
   *   The key of the journey. Required if not using ID. Prefix the parameter
   *   with key:. For example, /interactions/key:{key}.
   * @param null|int $versionNumber
   *   Version number of the journey to delete. If no version is specified, ALL
   *   versions associated with the provided ID will be deleted.
   *
   * @return array|bool|null
   *   The result of the API call, or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/deleteInteractionById.htm
   */
  public function deleteJourney($id, $versionNumber = NULL) {
    $machineName = 'delete_journey';
    $params = !$versionNumber ? [] : ['versionNumber' => $versionNumber];
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id], $params);
  }

  /**
   * Retrieves an audit log of a journey and its versions by ID or key. Pass in different actions to see history about creating, modifying, activating, deactivating, stopping, and deleting a journey.
   *
   * @param string $id
   *   The ID or key of the audit log to retrieve. Required if not using a key.
   *   The ID returns the audit log for all versions of the journey, unless a
   *   versionNumber is provided.
   *   or
   *   The key of the audit log to retrieve. Required if not using ID. Prefix
   *   the parameter with key:. For example, /interactions/key:{key}.
   * @param string $action
   *   The actions used to build your audit log. Specify all to return all
   *   actions. Use one of these possible values:
   *     all.
   *     create.
   *     modify.
   *     publish.
   *     unpublish.
   *     delete.
   * @param null|int $versionNumber
   *   The version number of the journey audit log to retrieve.
   *
   * @return array|bool|null
   *   The result of the API call, or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getInteractionAuditLog.htm
   */
  public function getJourneyAuditLog($id, $action, $versionNumber = NULL) {
    $machineName = 'get_journey_audit_log';
    $params = !$versionNumber ? [] : ['versionNumber' => $versionNumber];
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id, '[action]' => $action], $params);
  }

  /**
   * Checks the status of a publication.
   *
   * @param string $statusId
   *   The statusId provided by a successful POST request to schedule for a
   *   specific version of a journey.
   *
   * @return array|bool|null
   *   The result of the API call, or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/getPublishStatus.htm
   */
  public function getPublishStatus($statusId) {
    $machineName = 'get_publish_status';
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[statusId]' => $statusId]);
  }

  /**
   * Stops a running journey. To call this resource, assign your API Integration the Automation | Interactions | Read scope.
   *
   * @param string $id
   *   The ID of the journey to stop, expressed in the form of a GUID (UUID).
   * @param int|null $versionNumber
   *   The version number of the journey to stop.
   *
   * @return array|bool|null
   *   The result of the API call, or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/postStopInteractionById.htm
   */
  public function stopJourney($id, $versionNumber = NULL) {
    $machineName = 'stop_journey';
    $params = !$versionNumber ? [] : ['versionNumber' => $versionNumber];
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id], $params);
  }

  /**
   * Publishes a journey version asynchronously. To call this resource, assign your API Integration the Automation | Interactions | Read scope.
   *
   * @param string $id
   *   The ID of the journey to publish expressed in the form of a GUID (UUID).
   * @param int|null $versionNumber
   *   Version number of the journey to publish.
   *
   * @return array|bool|null
   *   The result of the API call, or FALSE on failure.
   *
   * @see https://developer.salesforce.com/docs/atlas.en-us.noversion.mc-apis.meta/mc-apis/postPublishInteractionById.htm
   */
  public function publishJourneyVersion($id, $versionNumber = NULL) {
    $machineName = 'publish_journey_version';
    $params = !$versionNumber ? [] : ['versionNumber' => $versionNumber];
    return $this->apiCall($this->moduleName, $machineName, new \stdClass(), ['[id]' => $id], $params);
  }

}
