<?php

namespace Drupal\commerce_product_variation_csv;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;

class CsvHandler {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Constructs a new CsvHandler object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Gets the CSV column names from the field definitions.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions
   *   The field definitions.
   *
   * @return array
   *   The column names.
   */
  public function getColumnNames(array $field_definitions): array {
    $column_names = [];
    foreach ($field_definitions as $field_name => $definition) {
      $properties = array_keys($this->getFieldProperties($definition));

      // If there is only one property, do not require multiple columns.
      if (count($properties) === 1) {
        $column_names[] = $field_name;
      }
      // Create a column for each property on the field.
      else {
        foreach ($properties as $property_name) {
          $column_names[] = $field_name . '__' . $property_name;
        }
      }
    }

    return $column_names;
  }

  /**
   * Gets the field's properties.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field definition.
   *
   * @return array|\Drupal\Core\TypedData\DataDefinitionInterface[]
   *   The field properties.
   */
  public function getFieldProperties(FieldDefinitionInterface $definition): array {
    $storage = $definition->getFieldStorageDefinition();
    $properties = $storage->getPropertyDefinitions();
    // Filter out all computed properties, these cannot be set.
    $properties = array_filter($properties, function (DataDefinitionInterface $definition) {
      return !$definition->isComputed();
    });

    if ($definition->getType() === 'image') {
      // @todo add support for image fields via filenames.
      unset($properties['target_id']);

      // Remove width and height from image references. They are technically
      // dynamically populated, but cannot be marked computed since they use
      // normal field storage.
      unset($properties['width'], $properties['height']);
    }

    return $properties;
  }

  /**
   * Gets the variation type's field definitions.
   *
   * @param string $variation_type_id
   *   The variation type ID.
   *
   * @return \Drupal\Core\Field\FieldDefinitionInterface[]
   *   The field definitions.
   */
  public function getVariationFieldDefinitions(string $variation_type_id): array {
    $field_definitions = $this->entityFieldManager->getFieldDefinitions('commerce_product_variation', $variation_type_id);
    $field_definitions = array_filter($field_definitions, [$this, 'filterIgnoredFields']);
    return $field_definitions;
  }

  /**
   * Filters ignored fields.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $definition
   *   The field definition.
   *
   * @return bool
   *   To filter or not.
   */
  protected function filterIgnoredFields(FieldDefinitionInterface $definition): bool {
    return !$definition->isInternal() &&
      $definition->isDisplayConfigurable('form') &&
      !in_array($definition->getName(), $this->getIgnoredFieldNames(), TRUE);
  }

  /**
   * Gets the ignored field names.
   *
   * @return array
   *   The field names.
   */
  protected function getIgnoredFieldNames(): array {
    return [
      'uid',
      'created',
      'changed',
      'product_id',
    ];
  }

  /**
   * Gets the variation type for the product.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   *
   * @return string
   *   The variation type ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getProductTypeVariationTypeId(ProductInterface $product): string {
    $product_type_storage = $this->entityTypeManager->getStorage('commerce_product_type');
    $product_type = $product_type_storage->load($product->bundle());
    assert($product_type instanceof ProductTypeInterface);
    return $product_type->getVariationTypeId();
  }

}
