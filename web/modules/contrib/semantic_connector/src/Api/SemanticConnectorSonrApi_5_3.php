<?php

namespace Drupal\semantic_connector\Api;
use Drupal\Component\Serialization\Json;

/**
 * Class SemanticConnectorSonrApi_5_3
 *
 * API Class for the version 5.3
 */
class SemanticConnectorSonrApi_5_3 extends SemanticConnectorSonrApi_4_6 {

  /**
   * Adds a new custom search field for the suggestion call.
   *
   * @param string $label
   *   The label of the custom search field.
   * @param string $field
   *   The name of the custom search field, e.g. 'content_type'.
   * @param string $search_space_id
   *   The ID of the search space to add the custom search field for.
   *
   * @return boolean
   *   TRUE if field is added, otherwise FALSE.
   */
  public function addCustomSearchField($label, $field, $search_space_id = '') {
    $resource_path = $this->getApiPath() . 'config/search/add';
    $type = 'dyn_lit_' . str_replace('-', '_', $type);
    $post_parameters = array(
      'label' => $label,
      'name' => $type,
    );

    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
    ));

    if ($result !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Deletes a custom search field for the suggestion call.
   *
   * @param string $field
   *   The name of the custom search field.
   *   Must start with 'dyn_lit_', e.g. 'dyn_lit_content_type'.
   * @param string $search_space_id
   *   The ID of the search space to delete the custom search field for.
   *
   * @return boolean
   *   TRUE if field is deleted, otherwise FALSE.
   */
  public function deleteCustomSearchField($field, $search_space_id = '') {
    $resource_path = $this->getApiPath() . 'config/search/delete';
    $type = 'dyn_lit_' . str_replace('-', '_', $type);
    $post_parameters = array(
      'name' => $type,
    );

    $result = $this->connection->post($resource_path, array(
      'data' => $post_parameters,
    ));

    if ($result !== FALSE) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Get concept suggestions from a given search string.
   *
   * @param string $search_string
   *   The string to get suggestions for
   * @param string $search_space_id
   *   The ID of the search space to use for the suggestions.
   * @param array $parameters
   *   array(
   *    'locale' => (string) 'en',  (default: 'en')
   *    'count'  => (int)    10,    (default:   10)
   *  )
   *
   * @return boolean|array
   *   Array of concepts
   *   array(
   *    'id'      => (string) URI of concept
   *    'label'   => (string) prefLabel of concept
   *    'context' => (string) prefLabel of the broader concept
   *    'field'   => (string) URI of conceptScheme
   *  )
   */
  public function suggest($search_string, $search_space_id = '', $parameters = []) {
    $resource_path = $this->getApiPath() . 'suggest/multi';

    $get_parameters = array_merge([
      'searchString' => $search_string,
      'locale' => 'en',
      'count' => 10,
      'format' => 'json',
    ], $parameters);

    $result = $this->connection->get($resource_path, array(
      'query' => $get_parameters,
    ));

    $concepts = Json::decode($result);

    if (!is_array($concepts)) {
      return FALSE;
    }

    return $concepts;
  }

}
