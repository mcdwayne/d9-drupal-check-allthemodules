<?php

namespace Drupal\semantic_connector\Api;
use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorPPTApi_6_2
 *
 * API Class for the version 6.2. 6.1 is the first version that supports PPT API
 * versioning and 6.2 is the first version available for both PPX and PPT,
 * that's why the class is required even though there was no API change
 * interesting for the Drupal integration.
 */
class SemanticConnectorPPTApi_6_2 extends SemanticConnectorPPTApi_6_0 {
  /**
   * Get all classifiers for a specific PoolParty project.
   *
   * @param string $project_id
   *   The project UUID to get the classifiers for.
   *
   * @return array
   *   An array of classifiers, each one is an associative array including
   *   following keys:
   *   - isOnline (boolean) --> Online status
   *   - language (String) --> Language of classifier (en|de|es|fr|...)
   *   - name (String) --> Classifier name
   *   - status (String) --> Status
   *   - uri (String) --> Classifier id
   */
  public function getClassifiers($project_id) {
    $resource_path = $this->getApiPath() . 'classification/' . $project_id . '/classifiers';

    $result = $this->connection->get($resource_path);
    $classifier_data = Json::decode($result);

    $classifiers = [];
    if (is_array($classifier_data) && isset($classifier_data['jsonClassifierList'])) {
      $classifiers = $classifier_data['jsonClassifierList'];
    }
    return $classifiers;
  }
}
