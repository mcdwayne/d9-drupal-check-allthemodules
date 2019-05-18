<?php

namespace Drupal\digitalmeasures_migrate\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fetches DM profile fragment XML given a DM user ID.
 *
 * @MigrateProcessPlugin(
 *   id = "digitalmeasures_fragment_fetch"
 * )
 */
class FragmentFetch extends ProcessPluginBase implements ContainerFactoryPluginInterface {

  /**
   * @var \Drupal\Core\Database\Connection;
   */
  protected $database;

  /**
   * FragmentLookup constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static (
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function transform($value, MigrateExecutableInterface $migrate_executable, Row $row, $destination_property) {
    // Return NULL if no value.
    if (empty($value)) {
      return NULL;
    }

    // Get the ID and the XML field from the staging table...
    $query = $this->database->select('digitalmeasures_migrate_profile', 'pf')
      ->fields('pf', [
        'id',
        'xml',
      ]);

    // ...for the given UID.
    $query->condition('userId', $value);

    // Limit to a category if given.
    if (isset($this->configuration['category'])) {
      $query->condition('category', $this->configuration['category']);
    }

    // Get the results.
    $result = $query->execute()->fetchAllAssoc('id');

    // Return only the XML if only one result is present.
    if (count($result) == 1) {
      $item = reset($result);
      return $item->xml;
    }

    // If no results, return NULL so Migrate doesn't barf.
    if (empty($result)) {
      return NULL;
    }

    // Otherwise just return everything.
    return $result;
  }

}