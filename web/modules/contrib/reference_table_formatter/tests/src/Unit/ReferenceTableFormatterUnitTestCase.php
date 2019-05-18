<?php

namespace Drupal\Tests\reference_table_formatter\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * A base class for testing reference table formatter.
 */
abstract class ReferenceTableFormatterUnitTestCase extends UnitTestCase {

  /**
   * T-shirt mock entities.
   *
   * @return array
   *   Mock tshirt entities.
   */
  protected function tshirtMockEntities() {
    return [
      [
        // Test with enough weights to ensure the sort works.
        'title' => $this->getMockField('title', 'Title', 'Red Medium T', -5, FALSE),
        'field_color' => $this->getMockField('field_color', 'Color', 'Red', 3),
        'field_price' => $this->getMockField('field_price', 'Price', '$1.00', 1),
        'field_size' => $this->getMockField('field_size', 'Size', 'M', 2),
        // Test non-configurable fields are hidden.
        'field_sku' => $this->getMockField('field_sku', 'SKU', 'T1234', 0, FALSE),
      ],
      [
        // Test with enough weights to ensure the sort works.
        'title' => $this->getMockField('title', 'Title', 'Green Large T', -5, FALSE),
        'field_color' => $this->getMockField('field_color', 'Color', 'Green', 3),
        'field_price' => $this->getMockField('field_price', 'Price', '$2.00', 1),
        'field_size' => $this->getMockField('field_size', 'Size', 'L', 2),
        'field_sku' => $this->getMockField('field_sku', 'SKU', 'T1235', 0, FALSE),
      ],
    ];
  }

  /**
   * Get a mock field for testing.
   *
   * @param string $field_name
   *   The field name.
   * @param string $title
   *   The field label value.
   * @param string $value
   *   The value of the field.
   * @param int $weight
   *   (optional) The weight to sory by.
   * @param bool $is_configurable
   *   (optional) TRUE if the field is display configurable otherwise FALSE.
   * @param string $entity_type
   *   (optional) The entity type the field belongs to.
   *
   * @return array
   *   A mock field.
   */
  protected function getMockField($field_name, $title, $value, $weight = 0, $is_configurable = TRUE, $entity_type = 'node') {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type
      ->expects($this->any())
      ->method('getEntityTypeId')
      ->willReturn($entity_type);

    $field_definition = $this->getMock('Drupal\Core\Field\FieldDefinitionInterface');
    $field_definition
      ->expects($this->any())
      ->method('isDisplayConfigurable')
      ->with('view')
      ->willReturn($is_configurable);

    $field = $this->getMock('Drupal\Core\Field\FieldItemListInterface');
    $field
      ->expects($this->any())
      ->method('getFieldDefinition')
      ->willReturn($field_definition);

    return [
      '#field_name' => $field_name,
      '#title' => $title,
      '#value' => $value,
      '#weight' => $weight,
      '#items' => $field,
      '#object' => $entity_type,
    ];
  }

  /**
   * Get a mock entity manager for testing.
   *
   * @return \Drupal\Core\Entity\EntityManagerInterface
   *   A mock entity manager.
   */
  protected function getEntityManager($mock_fields) {

    $manager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');

    // Mock the getDefinition method of the entity manager.
    $manager
      ->expects($this->any())
      ->method('getDefinition')
      ->willReturnCallback(function ($entity_type_id) {

        $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');

        // Allow us to mock different key methods for different entity types.
        $get_key_method = $entity_type
          ->expects($this->any())
          ->method('getKey');

        if ($entity_type_id === 'node') {
          $get_key_method->willReturn('title');
        }

        return $entity_type;
      });

    // Mock the getStorage method of the entity manager.
    $manager
      ->expects($this->any())
      ->method('getStorage')
      ->willReturnCallback(function ($entity_type_id) use ($mock_fields) {

        $entity_storage = $this->getMock('Drupal\Core\Entity\EntityStorageInterface');

        // Allow us to mock different load methods for different entity types.
        $load_method = $entity_storage
          ->expects($this->any())
          ->method('load');

        if ($entity_type_id === 'entity_view_display') {
          $entity_view_display = $this->getMock('Drupal\Core\Entity\Display\EntityViewDisplayInterface');
          $entity_view_display
            ->expects($this->any())
            ->method('buildMultiple')
            ->willReturn($mock_fields);

          $load_method->willReturn($entity_view_display);
        }

        return $entity_storage;
      });

    return $manager;
  }

  /**
   * Get a mock renderer for testing.
   *
   * @return \Drupal\Core\Render\RendererInterface
   *   A mock renderer.
   */
  protected function getRenderer() {
    $renderer = $this->getMock('Drupal\Core\Render\RendererInterface');
    $renderer
      ->expects($this->any())
      ->method('render')
      ->willReturnCallback(function ($field) {
        // Ensure the label is hidden.
        $this->assertEquals('hidden', $field['#label_display']);
        return $field['#value'];
      });
    return $renderer;
  }

}
