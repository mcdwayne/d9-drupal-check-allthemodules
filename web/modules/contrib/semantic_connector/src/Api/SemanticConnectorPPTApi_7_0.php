<?php

namespace Drupal\semantic_connector\Api;

use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorPPTApi_7_0
 *
 * API Class for the version 7.0
 */
class SemanticConnectorPPTApi_7_0 extends SemanticConnectorPPTApi_6_2 {
  /**
   * Adds a literal to an existing concept
   *
   * @param string $project_id
   *  The ID of the PoolParty project.
   * @param string $concept_uri
   *  The URI of the Concept.
   * @param string $property
   *  The SKOS property. Possible values are:
   *  - preferredLabel
   *  - alternativeLabel
   *  - hiddenLabel
   *  - definition
   *  - scopeNote
   *  - example
   *  - notation
   * @param string $label
   *  The RDF literal to add.
   * @param string $language
   *  The attribute language.
   *
   * @return mixed
   *  Status: 200 - OK
   */
  public function addLiteral($project_id, $concept_uri, $property, $label, $language = NULL) {
    $resource_path = $this->getApiPath() . 'thesaurus/' . $project_id . '/addLiteral';
    $post_parameters = array(
      'resourceUri' => $concept_uri,
      'label' => $label,
      'property' => $property,
    );

    if (!is_null($language) && !empty($language)) {
      $post_parameters['language'] = $language;
    }

    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
    ));

    return $result;
  }
}
