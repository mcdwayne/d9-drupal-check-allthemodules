<?php

namespace Drupal\migrate_source_jsonpath\Plugin\migrate\process;

use Drupal\migrate\MigrateException;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use JsonPath\JsonObject;

/**
 * Returns value of input array or JSON string using JSONPath.
 *
 * Example of usage:
 * @code
 * process:
 *   title:
 *     -
 *       plugin: get_by_jsonpath
 *       source: source_field
 *       selector: '$[items][*][title]'
 *       smart_get: true
 * @endcode
 *
 * @MigrateProcessPlugin(
 *   id = "get_by_jsonpath"
 * )
 */
class GetByJsonPath extends ProcessPluginBase {

  /**
   * Flag indicating whether there are multiple values.
   *
   * @var bool
   */
  protected $multiple;

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    $default_values = [
      'selector' => NULL,
      'smart_get' => TRUE,
    ];
    $this->configuration += $default_values;

    if(empty($this->configuration['selector'])) {
      throw new MigrateException('get_by_jsonpath: Selector should not be empty.');
    }

    try {
      $json = new JsonObject($value, $this->configuration['smart_get']);
      $result = $json->get($this->configuration['selector']);
      $this->multiple = TRUE;
    }
    catch (\Exception $exception) {
      throw new MigrateException('get_by_jsonpath: ' . $exception->getMessage());
    }

    // If nothing was found - return empty array.
    if (FALSE === $result) {
      $this->multiple = FALSE;
      $result = [];
    }

    // If returned only one value.
    if (1 == count($result)) {
      $this->multiple = FALSE;
      $result = reset($result);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function multiple() {
    return $this->multiple;
  }

}
