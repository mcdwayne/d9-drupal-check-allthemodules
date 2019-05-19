<?php


namespace Drupal\semantic_connector\Api;
use Drupal\Component\Serialization\Json;
use Drupal\semantic_connector\SemanticConnectorWatchdog;

/**
 * Class SemanticConnectorPPXApi_5_3
 *
 * API Class for the version 5.3
 */
class SemanticConnectorPPXApi_5_3 extends SemanticConnectorPPXApi_4_6 {
  /**
   * Extract categories from given data.
   *
   * @param mixed $data
   *   Can be either a string for normal text-extraction of a file-object for
   *   text extraction of the file content.
   * @param string $language
   *   The iso-code of the language of the data.
   * @param array $parameters
   *   Additional parameters to forward to the API (e.g., projectId).
   * @param string $data_type
   *   The type of the data. Can be one of the following values:
   *   - "text" for a text
   *   - "url" for a valid URL
   *   - "file" for a file object with a file ID
   *   - "file direct" for all other files without an ID
   *
   * @return object
   *   Object of categories.
   */
  public function extractCategories($data, $language, array $parameters = array(), $data_type = '') {
    // Offer the possibility to support a different value for this function.
    $categories = NULL;

    $input = array(
      'data' => $data,
      'language' => $language,
      'parameters' => $parameters,
      'data type' => $data_type,
    );
    \Drupal::moduleHandler()->alter('semantic_connector_ppx_extractCategories', $this, $categories, $input);

    $result = NULL;
    if (is_null($categories)) {
      $resource_path = $this->getApiPath() . 'categorization';
      if (empty($data_type)) {
        $data_type = $this->getTypeOfData($data);
      }
      $parameters['disambiguation'] = TRUE;

      switch ($data_type) {
        // Extract categories from a given text.
        case 'text':
          $post_parameters = array_merge(array(
            'text' => $data,
            'language' => $language,
          ), $parameters);
          $result = $this->connection->post($resource_path, array(
            'data' => $post_parameters,
          ));
          break;

        // Extract categories from a given URL.
        case 'url':
          $post_parameters = array_merge(array(
            'url' => $data,
            'language' => $language,
          ), $parameters);
          $result = $this->connection->post($resource_path, array(
            'data' => $post_parameters
          ));
          break;

        // Extract categories from a given file uploaded via file field.
        case 'file':
          // Check if the file is in the public folder
          // and the PoolParty GraphSearch server can read it.
          if ($wrapper = \Drupal::service('stream_wrapper_manager')->getViaUri('public://')) {
            $public_path = $wrapper->realpath();
            $file_path = \Drupal::service('file_system')->realpath($data->getFileUri());
            if (strpos($file_path, $public_path) !== FALSE) {
              $post_parameters = array_merge(array(
                'file' => '@' . $file_path,
                'language' => $language,
              ), $parameters);
              $result = $this->connection->post($resource_path, array(
                'data' => $post_parameters,
                'headers' => array('Content-Type' => 'multipart/form-data'),
              ));
            }
          }
          break;

        // Extract categories from a given file
        case 'file direct':
          $post_parameters = array_merge(array(
            'file' => '@' . $data->file_path,
            'language' => $language,
          ), $parameters);
          $result = $this->connection->post($resource_path, array(
            'data' => $post_parameters,
            'headers' => array('Content-Type' => 'multipart/form-data'),
          ));
          break;

        default:
          SemanticConnectorWatchdog::message('PPX API', 'The type of the data to extract categories is not supported.');
          break;
      }

      $categories = Json::decode($result);
    }

    // Files have additional information we don't need --> remove it.
    if (is_array($categories) && isset($categories['title'])) {
      $categories = $categories['title'];
    }
    if (is_array($categories) && isset($categories['text'])) {
      $categories = $categories['text'];
    }

    return $categories;
  }
}