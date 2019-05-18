<?php

namespace Drupal\digitalmeasures_migrate\Plugin\migrate\process;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\ProcessPluginBase;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Fetches DM profile fragment IDs given a Digital Measures User ID and category.
 *
 * @MigrateProcessPlugin(
 *   id = "digitalmeasures_fragment_lookup"
 * )
 */
class FragmentLookup extends ProcessPluginBase implements ContainerFactoryPluginInterface {

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

    $query = $this->database->select('digitalmeasures_migrate_profile', 'pf')
      ->fields('pf', [
        'id',
      ]);

    $query->condition('userId', $value);

    if (isset($this->configuration['category'])) {
      $query->condition('category', $this->configuration['category']);
    }

    $result = $query->execute()->fetchCol();

    $out = [];
    foreach ($result as $item) {
      $out[] = [
        $item,
      ];
    }

    return $out;
  }

}