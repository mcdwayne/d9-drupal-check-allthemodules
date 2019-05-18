<?php

namespace Drupal\external_data_source\Plugin;

/**
 * Base class for External Data Source plugins.
 */
abstract class ExternalDataSourceBase implements ExternalDataSourceInterface {

  /**
   * The request from the controller
   *
   * @var \Symfony\Component\HttpFoundation\Request $request
   */
  public $request;

  /**
   * Requested result count
   *
   * @var integer $count
   */
  public $count;

  /**
   * Requested result string search query
   *
   * @var string $q
   */
  public $q;

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->pluginDefinition['name'];
  }

  /**
   * {@inheritdoc}
   */
  public function getDescription() {
    return $this->pluginDefinition['description'];
  }

  /**
   * getResponse
   * Call WS to retrieve data
   */
  public abstract function getResponse();

  /**
   * getRequest
   * getting sent request
   *
   * @return \Symfony\Component\HttpFoundation\Request $request
   */
  public function getRequest() {
    return $this->request;
  }

  /**
   * Detect & convert special char to UTF8
   *
   * @author Amine Cherif <maccherif200@gmail.com>
   *
   * @param array $data
   *
   * @return array
   */
  public function sanitizeArray(array $data) {
    $stringCleaner = new UTF8Utils();
    $cleanOptions = [];
    foreach ($data as $key => $value) {
      $cleanOptions[$stringCleaner::convertToUTF8($key)] = $stringCleaner::convertToUTF8($value);
    }
    return $cleanOptions;
  }

  /**
   * formatResponse
   *
   * @param array $response
   * Formatting data retrieved from ws to match [{"value":"","label":""},
   *   {"value":"", "label":""}] return array $collection retrieved suggestions
   *
   * @return array $collection
   */
  public function formatResponse(array $response) {
    $collection = [];
    foreach ($response as $entry) {
      $collection[] = [
        'value' => $entry->label,
        'label' => $entry->label . ' (' . $entry->value . ')',
      ];
    }
    return $collection;
  }

}
