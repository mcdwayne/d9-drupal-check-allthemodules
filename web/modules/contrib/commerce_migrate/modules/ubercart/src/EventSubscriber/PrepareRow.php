<?php

namespace Drupal\commerce_migrate_ubercart\EventSubscriber;

use Drupal\commerce_migrate\Utility;
use Drupal\field\Plugin\migrate\source\d6\Field;
use Drupal\field\Plugin\migrate\source\d6\FieldInstance as D6FieldInstance;
use Drupal\field\Plugin\migrate\source\d6\FieldInstancePerFormDisplay as D6FieldInstancePerFormDisplay;
use Drupal\field\Plugin\migrate\source\d6\FieldInstancePerFormDisplay as D7FieldInstancePerFormDisplay;
use Drupal\field\Plugin\migrate\source\d6\FieldInstancePerViewMode as D6FieldInstancePerViewMode;
use Drupal\field\Plugin\migrate\source\d7\FieldInstance as D7FieldInstance;
use Drupal\field\Plugin\migrate\source\d7\FieldInstancePerViewMode as D7FieldInstancePerViewMode;
use Drupal\field\Plugin\migrate\source\d7\ViewMode as D7ViewMode;
use Drupal\language\Plugin\migrate\source\d6\LanguageContentSettings;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate_plus\Event\MigrateEvents;
use Drupal\migrate_plus\Event\MigratePrepareRowEvent;
use Drupal\node\Plugin\migrate\source\d6\NodeType as D6NodeType;
use Drupal\node\Plugin\migrate\source\d7\NodeType as D7NodeType;
use Drupal\node\Plugin\migrate\source\d6\ViewMode as D6ViewMode;
use Drupal\taxonomy\Plugin\migrate\source\d6\TermNode;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Handles migrate_plus prepare row event.
 *
 * @package Drupal\commerce_migrate_ubercart\EventSubscriber
 */
class PrepareRow implements EventSubscriberInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Product node types.
   *
   * @var array
   */
  protected $productTypes = [];

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[MigrateEvents::PREPARE_ROW][] = 'onPrepareRow';
    return $events;
  }

  /**
   * Responds to prepare row event.
   *
   * Since products are nodes in Ubercart 6 and Ubercart 7 migrations, primarily
   * the field and node mirations are alterd to prevent the duplication of
   * products as nodes and that fields are on the correct entities. The approach
   * is to change the source entity type to commerce_product when the node is a
   * product node or the field is used on a product node. Some fields need to be
   * created on both a node and a product as well. These changes work in
   * conjunction with alteration in hook_migration_plugins_alter() in
   * commerce_migrate_ubercart.module.
   *
   * By modify the row early or creating a new row allows the migration to
   * behave as if an entity_type of commerce_product really exists on the source
   * site. And in doing so, other migration using a migration_lookup will have
   * the data needed in the map table.
   *
   * An example of this is the d7_field migration. This migration is altered to
   * use a custom source plugin which add rows to be processed. New rows are
   * added for the commerce_product version of the field if the field must be on
   * a product node and any other entity.
   *
   * Node type: Sets property 'product_type'.
   * Field: Set the entity_type.
   * Field instance, Field formatter, Field widget, View mode: Set the entity
   * type.
   *
   * @param \Drupal\migrate_plus\Event\MigratePrepareRowEvent $event
   *   The event.
   *
   * @see commerce_migrate_ubercart.module
   * @see \Drupal\commerce_migrate_ubercart\Plugin\migrate\source\uc7\Field
   */
  public function onPrepareRow(MigratePrepareRowEvent $event) {
    $migration = $event->getMigration();
    $row = $event->getRow();
    $source_plugin = $migration->getSourcePlugin();

    if (Utility::classInArray($source_plugin, [
      D6NodeType::class,
      TermNode::class,
      D7Nodetype::class,
    ])) {
      // For Node Type migrations, i.e. d6_node_type, set product_type so all
      // product type rows are skipped.
      $node_type = $row->getSourceProperty('type');
      $this->productTypes = $this->getProductTypes($migration);
      $row->setSourceProperty('product_type', TRUE);
      if (in_array($node_type, $this->productTypes)) {
        $row->setSourceProperty('product_type', NULL);
      }
    }

    // The d6_field migration.
    if (is_a($source_plugin, Field::class)) {
      $this->productTypes = $this->getProductTypes($migration);
      $field_name = $row->getSourceProperty('field_name');
      // Get all the instances of this field.
      $query = $this->connection->select('content_node_field', 'cnf')
        ->fields('cnfi', ['type_name'])
        ->distinct();
      $query->innerJoin('content_node_field_instance', 'cnfi', 'cnfi.field_name = cnf.field_name');
      $query->condition('cnf.field_name', $field_name);
      $instances = $query->execute()->fetchCol();
      // Determine if the field is on both a product type and node, or just one
      // of product type or node.
      $i = 0;
      foreach ($instances as $instance) {
        if (in_array($instance, $this->productTypes)) {
          $i++;
        }
      }
      if ($i > 0) {
        if ($i == count($instances)) {
          // If all bundles for this field are product types, then change the
          // entity type to 'commerce_product'.
          $row->setSourceProperty('entity_type', 'commerce_product');
        }
        else {
          // This field is used on both nodes and products. Set
          // ubercart_entity_type so that field storage is created for the
          // ubercart product.
          $row->setSourceProperty('ubercart_entity_type', 'commerce_product');
          $row->setSourceProperty('entity_type', 'node');
        }
      }
      else {
        // This field is used on just nodes. Set the entity_type to 'node'.
        $row->setSourceProperty('entity_type', 'node');
      }
    }

    if (Utility::classInArray($source_plugin, [
      D6FieldInstance::class,
      D6FieldInstancePerViewMode::class,
      D6FieldInstancePerFormDisplay::class,
      D6ViewMode::class,
    ], FALSE)) {
      if (!$this->setEntityType($row, $migration, $row->getSourceProperty('type_name'))) {
        $row->setSourceProperty('entity_type', 'node');
      }
    }

    if (Utility::classInArray($source_plugin, [
      D7FieldInstance::class,
      D7FieldInstancePerViewMode::class,
      D7FieldInstancePerFormDisplay::class,
      D7ViewMode::class,
    ], FALSE)) {
      // If needed, change the entity type to commerce_product.
      $this->setEntityType($row, $migration, $row->getSourceProperty('bundle'));
    }

    if (is_a($source_plugin, LanguageContentSettings::class)) {
      // There are two language_content_settings migrations, the core one for
      // nodes and one for products. Allow the core one to only save language
      // content settings for nodes and the latter for products.
      $node_type = $row->getSourceProperty('type');
      $this->productTypes = $this->getProductTypes($migration);
      $row->setSourceProperty('product_type', TRUE);
      if (in_array($node_type, $this->productTypes)) {
        $source = $row->getSource();
        $type = $source['constants']['target_type'];
        if ($type == 'node') {
          // This is the core language content settings migration, do not
          // migrate this product type row.
          $row->setSourceProperty('product_type', NULL);
        }
      }
    }
  }

  /**
   * Helper to set the correct entity type in the source row.
   *
   * @param \Drupal\migrate\Row $row
   *   The row object.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param string $type_name
   *   The type name.
   */
  protected function setEntityType(Row $row, MigrationInterface $migration, $type_name) {
    if ($this->productTypes == []) {
      $this->productTypes = $this->getProductTypes($migration);
    }
    if (in_array($type_name, $this->productTypes)) {
      $row->setSourceProperty('entity_type', 'commerce_product');
      return TRUE;
    }
    return FALSE;
  }

  /**
   * Helper to get the product types from the source database.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   *
   * @return array
   *   The product types.
   */
  protected function getProductTypes(MigrationInterface $migration) {
    if (!empty($this->productTypes)) {
      return $this->productTypes;
    }
    /** @var \Drupal\migrate\Plugin\migrate\source\SqlBase $source_plugin */
    $source_plugin = $migration->getSourcePlugin();
    if (method_exists($source_plugin, 'getDatabase')) {
      $this->connection = $source_plugin->getDatabase();
      if ($this->connection->schema()->tableExists('node_type')) {
        $query = $this->connection->select('node_type', 'nt')
          ->fields('nt', ['type'])
          ->condition('module', 'uc_product%', 'LIKE')
          ->distinct();
        $this->productTypes = [$query->execute()->fetchCol()];
      }
    }
    return reset($this->productTypes);
  }

}
