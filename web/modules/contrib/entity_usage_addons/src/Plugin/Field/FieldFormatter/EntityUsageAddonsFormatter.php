<?php

namespace Drupal\entity_usage_addons\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Plugin\Field\FieldFormatter\BaseFieldFileFormatterBase;

/**
 * Formatter that shows the file size in a human readable way.
 *
 * @FieldFormatter(
 *   id = "entity_usage_addons_formatter",
 *   label = @Translation("Entity Usage - Detailed"),
 *   field_types = {
 *     "integer"
 *   }
 * )
 */
class EntityUsageAddonsFormatter extends BaseFieldFileFormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    $settings = parent::defaultSettings();

    // Default expanded references.
    $settings['max_expanded'] = 3;

    // Default fields to show.
    $settings['show_fields'] = "entity";

    // Show header.
    $settings['show_header'] = FALSE;

    return $settings;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);

    $form['max_expanded'] = [
      '#title' => $this->t('Max number of references to expand'),
      '#description' => $this->t('Max number of references to expand.'),
      '#type' => 'select',
      // TODO: Expose these options in a YAML conf.
      '#options' => [
        0 => 0,
        1 => 1,
        3 => 3,
        5 => 5,
        10 => 10,
        20 => 20,
        50 => 50,
        100 => 100,
      ],
      '#default_value' => $this->getSetting('max_expanded'),
    ];

    $form['show_header'] = [
      '#title' => $this->t('Show Header'),
      '#description' => $this->t('Show Header?.'),
      '#type' => 'checkbox',
      '#default_value' => $this->getSetting('show_header'),
    ];

    $form['show_fields'] = [
      '#title' => $this->t('Show Fields'),
      '#description' => $this->t('Select the fields to display.'),
      '#type' => 'checkboxes',
      '#options' => [
        'entity' => $this->t('Entity'),
        'status' => $this->t('Status'),
        'type' => $this->t('Type'),
      ],
      '#default_value' => $this->getSetting('show_fields'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function viewValue(FieldItemInterface $item) {
    $entityType = $item->getEntity()->getEntityType()->id();
    $entityId = $item->value;
    $maxExpanded = $this->getSetting('max_expanded');
    $showFields = array_filter($this->getSetting('show_fields'));
    $showHeader = $this->getSetting('show_header');

    // TODO Dependency Inject.
    $entityUsageAddons = \Drupal::service('entity_usage_addons.usage');
    $itemCount = $entityUsageAddons->getUsageTotal($entityType, $entityId);

    if ($itemCount == 0 || $itemCount > $maxExpanded) {
      return $entityUsageAddons->linkedUsage($entityType, $entityId);
    }
    else {
      return $entityUsageAddons->detailedUsage($entityType, $entityId, $showFields, $showHeader);
    }
  }

}
