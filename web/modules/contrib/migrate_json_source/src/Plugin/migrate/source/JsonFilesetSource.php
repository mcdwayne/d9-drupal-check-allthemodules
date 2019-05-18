<?php

namespace Drupal\migrate_json_source\Plugin\migrate\source;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Utility\NestedArray;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Row;
use Drupal\migrate_json_source\JsonGlobIterator;
use FilesystemIterator;
use GlobIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * JSON file set source.
 *
 * @MigrateSource(
 *   id = "json_fileset",
 *   source_module = "migrate_json_source"
 * )
 */
class JsonFilesetSource extends SourcePluginBase implements ConfigurablePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return 'JSON encoded file set.';
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'id' => [
        'type' => 'string',
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    return new JsonGlobIterator($this->getConfiguration()['path']);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {
    $this->configuration = NestedArray::mergeDeep(
      $this->defaultConfiguration(),
      $configuration
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
