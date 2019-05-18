<?php

namespace Drupal\bigcommerce\Plugin\migrate\id_map;

use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Defines the BigCommerce sync migration map.
 *
 * It creates one map and one message table per BigCommerce sync to store the
 * relevant information.
 *
 * @PluginID("bigcommerce_sync")
 */
class Sync extends Sql {

  /**
   * Constructs a Sync object.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin ID for the migration process to do.
   * @param mixed $plugin_definition
   *   The configuration for the plugin.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to do.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $event_dispatcher
   *   The event dispatcher.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EventDispatcherInterface $event_dispatcher) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $event_dispatcher);

    // Remove bigcommerce_ from the migration ID so it is not duplicated.
    $machine_name = str_replace([':', 'bigcommerce_'], ['__', ''], $this->migration->id());
    $prefix_length = strlen($this->database->tablePrefix());
    $this->mapTableName = 'bigcommerce_map_' . mb_strtolower($machine_name);
    $this->mapTableName = mb_substr($this->mapTableName, 0, 63 - $prefix_length);
    $this->messageTableName = 'bigcommerce_message_' . mb_strtolower($machine_name);
    $this->messageTableName = mb_substr($this->messageTableName, 0, 63 - $prefix_length);
  }

}
