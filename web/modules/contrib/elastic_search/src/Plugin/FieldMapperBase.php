<?php

namespace Drupal\elastic_search\Plugin;

use Drupal\Component\Plugin\PluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base class for Field mapper plugin plugins.
 */
abstract class FieldMapperBase extends PluginBase implements FieldMapperInterface {

  /**
   * @var null
   */
  private $supported = NULL;

  /**
   * FieldMapperBase constructor.
   *
   * @param array  $configuration
   * @param string $plugin_id
   * @param mixed  $plugin_definition
   */
  public function __construct(array $configuration,
                              $plugin_id,
                              $plugin_definition) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array                                                     $configuration
   * @param string                                                    $plugin_id
   * @param mixed                                                     $plugin_definition
   *
   * @return static
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition
    );
  }

  /**
   * @return string
   */
  public function getElasticType() {
    //use the id from the plugin mapping to be a direct mapping to elasticsearch types
    return $this->getPluginId();
  }

  /**
   * @inheritdoc
   */
  public function getFormFields(array $defaults, int $depth = 0): array {
    return [];
  }

  /**
   * @return bool
   */
  public function supportsFields(): bool {
    return FALSE;
  }

  /**
   * @inheritDoc
   */
  public function getDslFromData(array $data): array {
    $data = $this->numberToBool($data);
    $data = $this->stringToNumeric($data);
    $data = $this->removeEmptyStrings($data);
    ksort($data);
    return $data;
  }

  /**
   * @param array $data
   *
   * @return array
   */
  protected function numberToBool(array $data) {
    // Note the order of arguments and the & in front of $value
    array_walk_recursive($data,
      function (&$value, $key) {
        if (is_int($value)) {
          $value = ($value ? TRUE : FALSE);
        }
      });
    return $data;
  }

  /**
   * @param array $data
   *
   * @return array
   */
  protected function stringToNumeric(array $data) {
    array_walk_recursive($data,
      function (&$value, $key) {
        if (is_numeric($value)) {
          $value = (strpos($value, '.') !== FALSE) ? (float) $value :
            (int) $value;
        }
      });
    return $data;
  }

  /**
   * @param array $data
   *
   * @return array
   */
  protected function removeEmptyStrings(array $data) {
    return array_filter($data,
      function ($var) {
        return !(is_string($var) && empty($var));

      });
  }

  /**
   * {@inheritdoc}
   */
  public function normalizeFieldData(string $id, array $data, array $fieldMappingData) {
    if (!array_key_exists('nested', $fieldMappingData) || (int) $fieldMappingData['nested'] !== 1) {
      //If not nested just return the value
      return (isset($data[0]) && array_key_exists('value', $data[0])) ? $data[0]['value'] : NULL;
    }
    //If nested then we need to pass back an array of values
    $out = [];
    foreach ($data as $datum) {
      if (isset($datum['value'])) {
        $out[] = $datum['value'];
      }
    }
    return !empty($out) ? $out : NULL;
  }

}
