<?php

namespace Drupal\commerce_product_variation_csv;

use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;

class CsvExporter {

  /**
   * The CSV handler.
   *
   * @var \Drupal\commerce_product_variation_csv\CsvHandler
   */
  protected $csvHandler;

  /**
   * Constructs a new CsvExporter object.
   *
   * @param \Drupal\commerce_product_variation_csv\CsvHandler $csv_handler
   *   The CSV handler.
   */
  public function __construct(CsvHandler $csv_handler) {
    $this->csvHandler = $csv_handler;
  }

  /**
   * Creates the CSV.
   *
   * @param \Drupal\commerce_product\Entity\ProductInterface $product
   *   The product.
   *
   * @return string
   *   The CSV data.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  public function createCsv(ProductInterface $product): string {
    $variation_type_id = $this->csvHandler->getProductTypeVariationTypeId($product);
    $field_definitions = $this->csvHandler->getVariationFieldDefinitions($variation_type_id);

    $columns = $this->csvHandler->getColumnNames($field_definitions);

    // @todo inject filesystem and write to private:// or temp:// for batch
    $csv = fopen('php://temp', 'rb+');
    fputcsv($csv, $columns);
    foreach ($product->getVariations() as $variation) {
      $row = [];
      foreach ($field_definitions as $field_name => $definition) {
        $properties = $this->csvHandler->getFieldProperties($definition);
        $field = $variation->get($field_name);

        if ($field->isEmpty()) {
          foreach ($properties as $property) {
            $row[] = NULL;
          }
          continue;
        }

        $field_item = $field->first();
        assert($field_item instanceof FieldItemInterface);

        if (count($properties) === 1) {
          $row[] = $this->massageExportPropertyValue($field_item, key($properties), $definition);
        }
        else {
          foreach ($properties as $property_name => $property) {
            $row[] = $this->massageExportPropertyValue($field_item, $property_name, $definition);
          }
        }
      }
      fputcsv($csv, $row);
    }
    rewind($csv);
    return stream_get_contents($csv);
  }

  /**
   * Massages the field item property value to CSV value.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $field_item
   *   The field item.
   * @param string $property_name
   *   The property name.
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   The field definition.
   *
   * @return mixed
   *   The CSV value.
   *
   * @throws \Drupal\Core\TypedData\Exception\MissingDataException
   */
  protected function massageExportPropertyValue(FieldItemInterface $field_item, string $property_name, FieldDefinitionInterface $field) {
    if ($field_item->isEmpty()) {
      return NULL;
    }
    $property = $field->getFieldStorageDefinition()->getPropertyDefinition($property_name);
    // Typed data definition class for the entity reference target_id property.
    if ($property instanceof DataReferenceTargetDefinition) {
      $entity = $field_item->get('entity')->getValue();
      assert($entity instanceof EntityInterface);
      return $entity->label();
    }
    return $field_item->get($property_name)->getValue();
  }

}
