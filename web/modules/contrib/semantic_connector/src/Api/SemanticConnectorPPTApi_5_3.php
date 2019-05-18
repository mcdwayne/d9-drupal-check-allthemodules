<?php

namespace Drupal\semantic_connector\Api;
use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorPPTApi_5_3
 *
 * API Class for the version 5.3
 */
class SemanticConnectorPPTApi_5_3 extends SemanticConnectorPPTApi_4_6 {

  /**
   * Get all history items of a PoolParty project.
   *
   * @param string $project_id
   *   The ID of the project to get history items for.
   * @param int $from_time
   *   Optional; Only history items after this time will be included.
   * @param int $to_time
   *   Optional; Only history items before this time will be included.
   * @param string[] $events
   *   Optional; Filter by event type.
   *   Possible values: resourceChangeAddition, resourceChangeRemoval,
   *     resourceChangeUpdate, addRelation, removeRelation, addLiteral,
   *     removeLiteral, updateLiteral, addCollectionMember,
   *     removeCollectionMember, createCollection, deleteCollection,
   *     importConcept, resourceChangeAddition, addCustomAttributeLiteral,
   *     removeCustomAttributeLiteral ,updateCustomAttributeLiteral,
   *     addCustomRelation, removeCustomRelation, addCustomClass,
   *     removeCustomClass
   *
   * @return array
   *   An array of history items.
   */
  public function getHistory($project_id, $from_time = NULL, $to_time = NULL, $events = array()) {
    $resource_path = $this->getApiPath() . 'history/' . $project_id;
    $get_parameters = array();

    if (!is_null($from_time)) {
      $get_parameters['fromTime'] = date('c', $from_time);
    }
    if (!is_null($to_time)) {
      $get_parameters['toTime'] = date('c', $to_time);
    }
    if (!empty($events)) {
      $get_parameters['events'] = $events;
    }

    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));
    $history_items = Json::decode($result);

    return $history_items;
  }

  /**
   * Returns a list of PoolParty user group names
   *
   * @return string[]
   *   Array of PoolParty user groups.
   */
  public function getUserGroups() {
    $resource_path = $this->getApiPath() . 'user/groups';
    $result = $this->connection->get($resource_path);
    $groups = Json::decode($result);

    return $groups;
  }

  /**
   * Get information about the extraction model for a PP project.
   *
   * @param string $project_id
   *   The ID of the PP project to get the extraction model info for.
   *
   * @return array|bool
   *   Associative array of extraction model info or FALSE in case of an error.
   *   Following keys are included:
   *   - lastBuildTime (string) --> Last extraction model build time
   *   - lastChangeTime (string) --> Last thesaurus change
   *   - upToDate (bool) --> Whether the extraction model is up-to-date or not
   */
  public function getExtractionModelInfo($project_id) {
    $resource_path = $this->getApiPath() . 'indexbuilder/' . $project_id;
    $result = $this->connection->get($resource_path);
    $extraction_model_info = Json::decode($result);

    return $extraction_model_info;
  }

  /**
   * Refresh the extraction model for a PP project
   *
   * @param string $project_id
   *   The ID of the PP project to refresh the extraction model for.
   *
   * @return array
   *   An associative array informing about the success of the refreshing
   *   containing following keys:
   *   - success (bool) --> TRUE if the refreshing worked, FALSE if not
   *   - message (string) --> This property is optional, but if it exists it
   *       includes more details about why the connection could not be
   *       established.
   *   - since PP 6.0 also "plainMessage" and "reportable"
   */
  public function refreshExtractionModel($project_id) {
    $refresh_info = array(
      'success' => FALSE,
      'message' => '',
    );

    $resource_path = $this->getApiPath() . 'indexbuilder/' . $project_id . '/refresh';
    $variables = array(
      'timeout' => 600 // Allowing up to 10 minutes for the process.
    );
    $result = $this->connection->get($resource_path, $variables);
    $result = Json::decode($result);
    if (is_array($result)) {
      $refresh_info = $result;
    }

    return $refresh_info;
  }
}