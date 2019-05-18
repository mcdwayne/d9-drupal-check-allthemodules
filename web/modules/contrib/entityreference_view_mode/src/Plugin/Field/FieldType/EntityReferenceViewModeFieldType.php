<?php

namespace Drupal\entityreference_view_mode\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'field_entityreference_view_mode_mode' field
 * type.
 *
 * @FieldType(
 *   id = "entityreference_view_mode_field_type",
 *   label = @Translation("Entity Reference & View Mode"),
 *   module = "entityreference_view_mode",
 *   description = @Translation("Field referencing a peice of content and an
 *   associated view mode."), default_widget =
 *   "entityreference_view_mode_field_widget", default_formatter =
 *   "entityreference_view_mode_field_formatter"
 * )
 */
class EntityReferenceViewModeFieldType extends FieldItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'target_type' => [],
      'settings' => [],
    ];
  }

  /**
   * Build al ist of available bundles.
   *
   * @return array
   *   Array of bundles.
   */
  public static function availableBundles($type) {
    $bundles_raw = entity_get_bundles($type);
    $bundles = [];
    foreach ($bundles_raw as $key => $value) {
      $bundles[$key] = $value['label'];
    }
    return $bundles;

  }

  public static function availableEntityTypes() {
    $definitions = \Drupal::entityManager()->getEntityTypeLabels(TRUE);
    return $definitions['Content'];
  }

  /**
   * Build a list of view modes.
   *
   * @return array
   *   Array of view modes.
   */
  public static function availableViewModes($type) {
    $view_mode_ids = \Drupal::entityQuery('entity_view_mode')
      ->condition('targetEntityType', $type)
      ->execute();

    $view_mode_classes = entity_load_multiple('entity_view_mode', $view_mode_ids);

    $view_modes = [];
    foreach ($view_mode_classes as $id => $class) {
      $view_modes[str_replace($type . '.', '', $id)] = $class->label();
    }
    return $view_modes;
  }


  /**
   * Update options.
   *
   * @param array $form
   *   Form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Formstate.
   *
   * @return mixed
   *   Returns the updated options.
   */
  public static function updateWidgetOptions($form, FormStateInterface &$form_state) {
    $form_state->setValue('saved_trigger', $form_state->getTriggeringElement());
    $trigger = $form_state->getTriggeringElement();
    $parents = $trigger['#array_parents'];
    array_pop($parents);


    return $form[$parents[0]];
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];


    $element['target_type'] = [
      '#type' => 'checkboxes',
      '#title' => t('Type of item to reference'),
      '#options' => $this->availableEntityTypes(),
      '#default_value' => $this->getSetting('target_type'),
      '#required' => TRUE,
      '#size' => 1,
    ];

    $settings = $this->getSetting('settings');
    foreach ($this->availableEntityTypes() as $key => $type) {
      $element['settings'][$key] = [
        '#type' => 'details',
        '#title' => $key,
        '#states' => [
          'visible' => [
            ':input[name="settings[target_type][' . $key . ']"]' => [
              ['checked' => TRUE],
            ],
          ],
        ],
      ];
      $bundles = $this->availableBundles($key);
      $element['settings'][$key]['bundles'] = [
        '#type' => 'checkboxes',
        '#title' => t('Enabled Bundles'),
        '#default_value' => $settings[$key]['bundles'],
        '#description' => t('Select enabled bundles'),
        '#options' => $bundles,
      ];
      $view_modes = $this->availableViewModes($key);
      $element['settings'][$key]['view_modes'] = [
        '#type' => 'checkboxes',
        '#title' => t('Enabled View Modes'),
        '#default_value' => $settings[$key]['view_modes'],
        '#description' => t('Select enabled view modes'),
        '#options' => $view_modes,
      ];
    }


    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    return [
      'columns' => [
        'target_type' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'content' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
        'view_mode' => [
          'type' => 'text',
          'size' => 'tiny',
          'not null' => FALSE,
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $isEmpty =
      empty($this->get('content')->getValue());
    return $isEmpty;
  }

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    $properties['target_type'] = DataDefinition::create('string')
      ->setLabel(t('Type Value'));
    $properties['content'] = DataDefinition::create('string')
      ->setLabel(t('Content Value'));
    $properties['view_mode'] = DataDefinition::create('string')
      ->setLabel(t('View Mode Value'));
    return $properties;
  }

}
