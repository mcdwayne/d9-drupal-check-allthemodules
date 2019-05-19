<?php

namespace Drupal\Tests\widget_engine\Traits;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\widget_engine\Entity\WidgetTypeInterface;
use Drupal\widget_engine\Entity\WidgetType;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field\Entity\FieldConfig;
use PHPUnit\Framework\TestCase;

/**
 * Provides methods to create widget type from given values.
 *
 * This trait is meant to be used only by test classes.
 */
trait WidgetTypeCreationTrait {

  /**
   * Creates a custom widget type based on default settings.
   *
   * @param array $values
   *   An array of settings to change from the defaults.
   *   Example: 'type' => 'foo'.
   *
   * @return \Drupal\widget_engine\Entity\WidgetType
   *   Created widget type.
   */
  protected function createWidgeType(array $values = []) {
    // Find a non-existent random type name.
    if (!isset($values['type'])) {
      do {
        $id = strtolower($this->randomMachineName(8));
      } while (WidgetType::load($id));
    }
    else {
      $id = $values['type'];
    }
    $values += [
      'label' => $id,
      'id' => $id,
    ];
    $type = WidgetType::create($values);

    $status = $type->save();
    if ($this instanceof TestCase) {
      $this->assertSame($status, SAVED_NEW, (new FormattableMarkup('Created widget type %type.', ['%type' => $type->id()]))->__toString());
    }
    else {
      $this->assertEqual($status, SAVED_NEW, (new FormattableMarkup('Created widget type %type.', ['%type' => $type->id()]))->__toString());
    }
    $this->widgetAddBodyField($type);
    return $type->id();
  }

  /**
   * Adds the default body field to a node type.
   *
   * @param \Drupal\widget_engine\Entity\WidgetTypeInterface $type
   *   A node type object.
   * @param string $label
   *   (optional) The label for the body instance.
   *
   * @return \Drupal\field\Entity\FieldConfig
   *   A Body field object.
   */
  public function widgetAddBodyField(WidgetTypeInterface $type, $label = 'Body') {
    // Add or remove the body field, as needed.
    $field_storage = FieldStorageConfig::loadByName('widget', 'body');
    if (empty($field_storage)) {
      $field_storage = FieldStorageConfig::create([
        'field_name' => 'body',
        'entity_type' => 'widget',
        'type' => 'text_with_summary',
        'cardinality' => '-1',
        'settings' => [],
      ]);
      $field_storage->save();
    }
    $field = FieldConfig::loadByName('widget', $type->id(), 'body');
    if (empty($field)) {
      $field = FieldConfig::create([
        'field_storage' => $field_storage,
        'bundle' => $type->id(),
        'label' => $label,
        'settings' => ['display_summary' => TRUE],
      ]);
      $field->save();

      // Assign widget settings for the 'default' form mode.
      entity_get_form_display('widget', $type->id(), 'default')
        ->setComponent('body', [
          'type' => 'text_textarea_with_summary',
        ])
        ->save();

      // Assign display settings for the 'default' and 'teaser' view modes.
      entity_get_display('widget', $type->id(), 'default')
        ->setComponent('body', [
          'label' => 'hidden',
          'type' => 'entity_reference_entity_view',
        ])
        ->save();

      // The teaser view mode is created by the Standard profile and therefore
      // might not exist.
      $view_modes = \Drupal::entityManager()->getViewModes('widget');
      if (isset($view_modes['teaser'])) {
        entity_get_display('widget', $type->id(), 'teaser')
          ->setComponent('body', [
            'label' => 'hidden',
            'type' => 'text_summary_or_trimmed',
          ])
          ->save();
      }
    }

    return $field;
  }

}
