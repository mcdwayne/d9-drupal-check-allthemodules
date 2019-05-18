<?php

namespace Drupal\commerce_inventory;

use Drupal\Component\Utility\Html;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;

/**
 * Provides a helper class for common Commerce Inventory uses.
 */
class InventoryHelper {

  /**
   * Build an array of contexts based on passed-in information.
   *
   * @param array $items
   *   Items to create contexts from.
   *
   * @return \Drupal\Core\Plugin\Context\ContextInterface[]
   *   An array of created contexts.
   */
  public static function buildContexts(array $items = []) {
    /** @var \Drupal\Core\Plugin\Context\ContextInterface[] $contexts */
    $contexts = [];

    foreach ($items as $item_id => $item) {
      if ($item instanceof EntityInterface) {
        $entity_type_id = $item->getEntityTypeId();
        $contexts[$item_id] = new Context(new ContextDefinition("entity:{$entity_type_id}", $item->getEntityType()->getLabel(), FALSE), $item);
      }
      elseif ($item instanceof LanguageInterface) {
        $contexts[$item_id] = new Context(new ContextDefinition("language", t('Language'), FALSE), $item);
        $cacheability = new CacheableMetadata();
        $cacheability->setCacheContexts(['languages:' . $item->getId()]);
        $contexts[$item_id]->addCacheableDependency($cacheability);
      }
      elseif (is_string($item)) {
        $contexts[$item_id] = new Context(new ContextDefinition("string"), $item);
      }
      elseif (is_int($item)) {
        $contexts[$item_id] = new Context(new ContextDefinition("integer"), $item);
      }
      else {
        $contexts[$item_id] = new Context(new ContextDefinition($item_id), $item);
      }
    }

    return $contexts;
  }

  /**
   * Generate an inventory quantity cache id.
   *
   * @param int $inventory_item_id
   *   The Inventory Item entity ID.
   * @param string $version
   *   The version to use. Options include: 'prefix', 'on_hand', 'available'.
   *
   * @return string
   *   The generated cid.
   */
  public static function generateQuantityCacheId($inventory_item_id, $version = 'prefix') {
    $cid_prefix = 'quantity:commerce_inventory_item:' . $inventory_item_id;

    switch ($version) {
      case 'available':
        return $cid_prefix . ':available';

      case 'minimum':
        return $cid_prefix . ':minimum';

      case 'on_hand':
        return $cid_prefix . ':on_hand';

      case 'prefix':
        return $cid_prefix;

      default:
        return $cid_prefix . ':' . Html::getId($cid_prefix);
    }
  }

  /**
   * Generate default cache tags for a specific Inventory Item.
   *
   * @param int $inventory_item_id
   *   The Inventory Item entity ID to generate default cache tags for.
   *
   * @return string[]
   *   The array generated cache tags.
   */
  public static function generateQuantityCacheTags($inventory_item_id) {
    return [
      'commerce_inventory_item:' . $inventory_item_id,
      self::generateQuantityCacheId($inventory_item_id),
      'quantity:commerce_inventory_item_list',
    ];
  }

  /**
   * Provides Views integration for Inventory Quantity-based fields.
   *
   * Alters the default field type Views data for
   * entity-reference-inventory-quantity-based fields. Modules defining new
   * entity-reference-inventory-quantity-based fields may use this function to
   * simplify Views integration.
   *
   * @param array &$data
   *   View data to alter.
   * @param \Drupal\Core\Field\FieldStorageDefinitionInterface $field_storage
   *   The field storage definition.
   *
   * @return array|null
   *   The array of field views data.
   */
  public static function alterFieldTypeViewsData(array &$data, FieldStorageDefinitionInterface $field_storage) {
    // Exit early if not an entity reference inventory quantity field.
    if ($field_storage->getType() != 'entity_reference_inventory_quantity') {
      return NULL;
    }

    $entity_type_manager = \Drupal::entityTypeManager();

    $entity_type_id = $field_storage->getTargetEntityTypeId();
    $entity_type = $entity_type_manager->getDefinition($entity_type_id);

    $target_entity_type_id = $field_storage->getSetting('target_type');
    $target_entity_type = $entity_type_manager->getDefinition($target_entity_type_id);
    $target_base_table = $target_entity_type->getDataTable() ?: $target_entity_type->getBaseTable();

    $field_name = $field_storage->getName();

    $table_alias = "{$entity_type_id}__{$field_name}";
    $table_mapping = $entity_type_manager->getStorage($entity_type_id)->getTableMapping();

    $args = [
      '@label' => $target_entity_type->getLabel(),
      '@field_name' => $field_name,
      '@field_label' => $field_storage->getLabel(),
    ];

    // Field for Entity Reference.
    // @todo $data[$table_alias][$field_name . '__target_id']['field']['id'] = 'entity_label';
    // @todo $data[$table_alias][$field_name . '__target_id']['field']['entity type field'] = $target_entity_type_id;
    // @todo $data[$table_alias][$field_name . '__target_id']['real field'] = $field_name . '_target_id';
    // @todo $data[$table_alias][$field_name . '__target_id']['title'] = t('@field_label - @label', $args);.

    // Field for Quantity.
    $data[$table_alias][$field_name . '__quantity']['field']['id'] = 'numeric';
    $data[$table_alias][$field_name . '__quantity']['real field'] = $field_name . '_quantity';
    $data[$table_alias][$field_name . '__quantity']['title'] = t('@field_label - Quantity', $args);

    // Provide a relationship for the entity type with the entity reference
    // field.
    $data[$table_alias][$field_name]['relationship'] = [
      'title' => t('@label referenced from @field_name', $args),
      'label' => t('@field_name: @label', $args),
      'group' => $entity_type->getLabel(),
      'id' => 'standard',
      'base' => $target_base_table,
      'entity type' => $target_entity_type_id,
      'base field' => $target_entity_type->getKey('id'),
      'relationship field' => $field_name . '_target_id',
    ];

    // Provide a reverse relationship for the entity type that is referenced by
    // the field.
    $args['@entity'] = $entity_type->getLabel();
    $args['@label'] = $target_entity_type->getLowercaseLabel();
    $pseudo_field_name = 'reverse__' . $entity_type_id . '__' . $field_name;
    $data[$target_base_table][$pseudo_field_name]['relationship'] = [
      'title' => t('@entity using @field_name', $args),
      'label' => t('@field_name', ['@field_name' => $field_name]),
      'group' => $target_entity_type->getLabel(),
      'help' => t('Relate each @entity with a @field_name set to the @label.', $args),
      'id' => 'entity_reverse',
      'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
      'entity_type' => $entity_type_id,
      'base field' => $entity_type->getKey('id'),
      'field_name' => $field_name,
      'field table' => $table_mapping->getDedicatedDataTableName($field_storage),
      'field field' => $field_name . '_target_id',
      'join_extra' => [
        [
          'field' => 'deleted',
          'value' => 0,
          'numeric' => TRUE,
        ],
      ],
    ];
  }

}
