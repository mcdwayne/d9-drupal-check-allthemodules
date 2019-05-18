<?php

namespace Drupal\Tests\field_group_ajaxified_multipage\Functional;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\Entity\EntityViewDisplay;

/**
 * Provides common functionality for the FieldGroupAjaxified test classes.
 */
trait FieldGroupAjaxifiedTrait {

  /**
   * Auxiliar method to create fields.
   *
   * @param string $field_name
   *   Field name.
   * @param string $node_type
   *   Node type.
   * @param \Drupal\Core\Entity\Entity\EntityViewDisplay $display
   *   Display.
   * @param array $options
   *   Options.
   */
  public function createField($field_name, $node_type, EntityViewDisplay $display, array $options = []) {
    $options += [
      'required' => FALSE,
    ];

    $field_storage = FieldStorageConfig::create([
      'field_name' => $field_name,
      'entity_type' => 'node',
      'type' => 'test_field',
    ]);
    $field_storage->save();

    $instance = FieldConfig::create([
      'field_storage' => $field_storage,
      'field_name' => $field_name,
      'bundle' => $node_type,
      'label' => $this->randomMachineName(),
      'required' => $options['required'],
    ]);

    $instance->save();

    // Set the field visible on the display object.
    $display_options = [
      'label' => 'above',
      'type' => 'field_test_default',
      'settings' => [
        'test_formatter_setting' => $this->randomMachineName(),
      ],
    ];
    $display->setComponent($field_name, $display_options);

    entity_get_form_display('node', $node_type, 'default')
      ->setComponent($field_name, [
        'type' => 'test_field_widget',
      ])
      ->save();
  }

  /**
   * Auxiliar method to update settings.
   *
   * @param string $bundle
   *   Bundle.
   * @param string $field
   *   Field group to update.
   * @param string $setting
   *   Settings to update.
   * @param mixed $value
   *   New value of setting.
   */
  public function updateSettingField($bundle, $field, $setting, $value) {
    $entity_type = 'node';
    $form_mode = 'default';
    $entity_form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load($entity_type . '.' . $bundle . '.' . $form_mode);
    $third_party_settings = $entity_form_display->getThirdPartySettings('field_group');
    $third_party_settings[$field]['format_settings'][$setting] = $value;
    $entity_form_display->setThirdPartySetting('field_group', $field, $third_party_settings[$field])->save();
  }

}
