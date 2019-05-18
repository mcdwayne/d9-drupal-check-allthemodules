<?php
/**
 * @file
 * Contains \Drupal\migrate_source_ical\Plugin\migrate\source\ical.
 */

namespace Drupal\migrate_source_ical\Plugin\migrate\source;

use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Source for Ical Feeds.
 *
 * @MigrateSource(
 *   id = "ical"
 * )
 */
class Ical extends SourcePluginBase {
  /**
   * The path to the iCal source.
   *
   * @var string
   */
  protected $path = '';

  /**
   * The request headers.
   *
   * @var array
   */
  protected $headers = [];

  /**
   * An array of source fields.
   *
   * @var array
   */
  protected $fields = [];

  /**
   * The field name that is a unique identifier.
   *
   * @var string
   */
  protected $identifier = '';

  /**
   * The reader class to read the JSON source file.
   *
   * @var string
   */
  protected $readerClass = '';

  /**
   * The JSON reader.
   *
   * @var resource
   */
  protected $reader;

  /**
   * The client class to create the HttpClient.
   *
   * @var string
   */
  protected $clientClass = '';

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, array $namespaces = array()) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $config_fields = array(
      'path',
      'fields',
      'identifier',
      'keys'
    );

    // Store the configuration data.
    foreach ($config_fields as $config_field) {
      if (isset($configuration[$config_field])) {
        $this->{$config_field} = $configuration[$config_field];
      }
      else {
        // Throw Exception if the migration file doesn't have the required keys.
        throw new MigrateException('The source configuration must include ' . $config_field . '.');
      }
    }

    // TODO:
    $this->readerClass = !isset($configuration['readerClass']) ? '\Drupal\migrate_source_ical\Plugin\migrate\ICALReader' : $configuration['readerClass'];

    // Create the ICAL reader that will process the request, and pass it configuration.
    $this->reader = new $this->readerClass($configuration);

  }

  /**
   * Return a count of all available source records.
   *
   * @return int
   *   The number of available source records.
   */
  public function _count($url) {
    return count($this->reader->getSourceFields($url));
  }

  /**
   * {@inheritdoc}
   */
  public function count() {
    return $this->_count($this->path);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = array();
    foreach ($this->configuration['keys'] as $key) {
      $ids[$key]['type'] = 'string';
    }
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->path;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return $this->fields;
  }

  /**
   * Get protected values.
   */
  public function get($item) {
    return $this->{$item};
  }

  /**
   * {@inheritdoc}
   */
  protected function initializeIterator() {
    $iterator = $this->reader->getSourceFieldsIterator($this->path);
    return $iterator;
  }
}
