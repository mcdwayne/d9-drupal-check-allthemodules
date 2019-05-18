<?php

namespace Drupal\commerce_product_variation_csv;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_product\Entity\ProductVariationInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\TypedData\EntityDataDefinition;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceDefinition;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

class CsvImporter {

  /**
   * The CSV handler.
   *
   * @var \Drupal\commerce_product_variation_csv\CsvHandler
   */
  protected $csvHandler;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The variation storage.
   *
   * @var \Drupal\commerce_product\ProductVariationStorageInterface
   */
  protected $variationStorage;

  /**
   * Constructs a new CsvImporter object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\commerce_product_variation_csv\CsvHandler $csv_handler
   *   The CSV handler.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CsvHandler $csv_handler) {
    $this->entityTypeManager = $entity_type_manager;
    $this->variationStorage = $entity_type_manager->getStorage('commerce_product_variation');
    $this->csvHandler = $csv_handler;
  }

  /**
   * Prepares a CSV file object.
   *
   * @param string $filename
   *   The CSV filename.
   *
   * @return \Drupal\commerce_product_variation_csv\CsvFileObject
   *   The prepared CSV file object.
   */
  public function prepareCsv(string $filename): CsvFileObject {
    return new CsvFileObject($filename, TRUE);
  }

  /**
   * Processes an CSV row from an import CSV.
   *
   * @param string $variation_type_id
   *   The destination variation type ID.
   * @param array $row
   *   The CSV row values.
   * @param \Drupal\Core\Field\FieldDefinitionInterface[] $field_definitions
   *   The variation field definitions.
   *
   * @return \Drupal\commerce_product\Entity\ProductVariationInterface
   *   The imported variation.
   *
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function processCsvRow(string $variation_type_id, array $row, array $field_definitions): ProductVariationInterface {
    if (!isset($row['sku'])) {
      throw new \InvalidArgumentException('SKU is required');
    }

    $variation = $this->variationStorage->loadBySku($row['sku']);
    if (!$variation instanceof ProductVariationInterface) {
      /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $variation */
      $variation = $this->variationStorage->create([
        // We do not set the product ID so that ProductVariation::postSave does
        // not load our product and save it out of order.
        'type' => $variation_type_id,
      ]);
    }

    foreach ($field_definitions as $field_name => $definition) {
      $properties = $this->csvHandler->getFieldProperties($definition);

      if (count($properties) === 1) {
        $property = reset($properties);
        $field_value = $this->massageImportPropertyValue($row[$field_name], $property, $definition);
      }
      else {
        $field_value = [];
        foreach ($properties as $property_name => $property) {
          $column_name = $field_name . '__' . $property_name;
          $field_value[$property_name] = $this->massageImportPropertyValue($row[$column_name], $property, $definition);
        }
      }
      $variation->get($field_name)->setValue($field_value);
    }
    $variation->save();

    return $variation;
  }

  /**
   * Imports an entire CSV.
   *
   * Best used over CLI for processing an entire CSV.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   * @param string $filename
   *   The CSV filename.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function importCsv(ProductInterface $product, string $filename) {

    $variation_type_id = $this->csvHandler->getProductTypeVariationTypeId($product);
    $field_definitions = $this->csvHandler->getVariationFieldDefinitions($variation_type_id);
    $columns = $this->csvHandler->getColumnNames($field_definitions);

    $csv = $this->prepareCsv($filename);
    while ($csv->valid()) {
      $current = $csv->current();
      if (count(array_keys($current)) !== count($columns)) {
        throw new \InvalidArgumentException('Mismatched columns');
      }

      $variation = $this->processCsvRow($variation_type_id, $current, $field_definitions);
      if (!$product->hasVariation($variation)) {
        $product->addVariation($variation);
      }

      $csv->next();
    }
    $product->save();
  }

  /**
   * Massages the imported CSV value to one accepted by the property definition.
   *
   * @param mixed $value
   *   The value.
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $property
   *   The property definition.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   The field definition.
   *
   * @return mixed
   *   The property value.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  protected function massageImportPropertyValue($value, DataDefinitionInterface $property, FieldDefinitionInterface $field) {
    // Typed data definition class for the entity reference target_id property.
    if ($property instanceof DataReferenceTargetDefinition) {
      $entity_property = $field->getFieldStorageDefinition()->getPropertyDefinition('entity');
      assert($entity_property instanceof DataReferenceDefinition);
      $target_definition = $entity_property->getTargetDefinition();
      assert($target_definition instanceof EntityDataDefinition);

      $target_entity_type_id = $target_definition->getEntityTypeId();
      try {
        $storage = $this->entityTypeManager->getStorage($target_entity_type_id);
        $entity_definition = $this->entityTypeManager->getDefinition($target_entity_type_id);
        if ($entity_definition === NULL) {
          return NULL;
        }

        // Try to load the entity using the value directly.
        $reference_target_entity = $storage->load($value);

        // The value returned an actual entity by reference, so we can keep
        // the provided value without having to massage it.
        if ($reference_target_entity) {
          return $value;
        }

        // Assume the value passed was an entity label (ie: Red as the label for
        // a color attribute value.)
        if (!$entity_definition->hasKey('label')) {
          return NULL;
        }
        $load_by_properties = [$entity_definition->getKey('label') => $value];
        if ($entity_definition->hasKey('bundle')) {
          $handler_settings = $field->getSetting('handler_settings');
          $load_by_properties[$entity_definition->getKey('bundle')] = $handler_settings['target_bundles'] ?? [];
        }
        $existing = $storage->loadByProperties($load_by_properties);

        // If we received entities, pick the first one.
        if (!empty($existing)) {
          $reference_target_entity = reset($existing);
          $value = $reference_target_entity->id();
        }
        // We did not receive an entity, so assume one was intended to be
        // created.
        else {
          $entity_values = [
            $entity_definition->getKey('label') => $value,
          ];
          if ($entity_definition->hasKey('bundle')) {
            $handler_settings = $field->getSetting('handler_settings');
            // @todo This will probably die when none are specified?
            $target_bundles = $handler_settings['target_bundles'] ?? [];
            $entity_values[$entity_definition->getKey('bundle')] = reset($target_bundles);
          }
          $reference_target_entity = $storage->create($entity_values);
          $reference_target_entity->save();

          $value = $reference_target_entity->id();
        }
      }
      catch (PluginException $e) {
        return NULL;
      }
    }

    return $value;
  }

}
