<?php

namespace Drupal\scheduling\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\MapItem;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\options\Plugin\Field\FieldType\ListStringItem;

/**
 * Defines the 'scheduling_mode' entity field type.
 *
 * @FieldType(
 *   id = "scheduling_mode",
 *   label = @Translation("Scheduling mode"),
 *   description = @Translation("A field for specifying the scheduling mode."),
 *   default_widget = "scheduling_mode",
 *   no_ui = TRUE,
 * )
 */
class SchedulingModeItem extends ListStringItem {

  public function getSettableOptions(AccountInterface $account = NULL) {

    $mode = isset($this->getFieldDefinition()->getSettings()['mode']) ? $this->getFieldDefinition()->getSettings()['mode'] : 'range';

    $settableOptions = [
      'unpublished' => new TranslatableMarkup('Unpublished'),
      'published' => new TranslatableMarkup('Published'),
    ];

    if ($mode === 'range') {
      $settableOptions['range'] = new TranslatableMarkup('Scheduled');
    }
    if ($mode === 'recurring') {
      $settableOptions['recurring'] = new TranslatableMarkup('Scheduled');
    }
    if ($mode === 'combined') {
      $settableOptions['range'] = new TranslatableMarkup('Range');
      $settableOptions['recurring'] = new TranslatableMarkup('Recurring');
    }

    return $settableOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function applyDefaultValue($notify = TRUE) {
    // Apply the default value of all properties.
    foreach ($this->getProperties() as $property) {
      $property->applyDefaultValue(FALSE);
    }
    return $this;
  }

}
