<?php

namespace Drupal\semantic_connector\Api;

use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorPPXApi_7_0
 *
 * API Class for the version 7.0.
 */
class SemanticConnectorPPXApi_7_0 extends SemanticConnectorPPXApi_6_2 {
  /**
   * Extract categories from given data.
   *
   * @param string $text
   *   The text to extract named entities for.
   * @param string $language
   *   The iso-code of the language of the data.
   * @param array $types
   *   The types of entities to extract.
   * @param array $parameters
   *   Additional parameters to forward to the API.
   *
   * @return array
   *   An array of named entity objects.
   */
  public function extractNamedEntities($text, $language, array $types, $parameters = []) {
    $resource_path = $this->getApiPath() . 'extract';

    $post_parameters = array_merge(array(
      'text' => $text,
      'language' => $language,
      'numberOfConcepts' => 0,
      'numberOfTerms' => 0,
    ), $parameters);

    // Add the NER parameters.
    for ($typecount = 0; $typecount < count($types); $typecount++) {
      $post_parameters['nerParameters[' . $typecount . '].type'] = $types[$typecount];
      $post_parameters['nerParameters[' . $typecount . '].method'] = 'MAXIMUM_ENTROPY';
    }

    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
    ));
    $entities = Json::decode($result);

    if (!empty($entities) && isset($entities['namedEntities'])) {
      return $entities['namedEntities'];
    }
    return [];
  }
}
