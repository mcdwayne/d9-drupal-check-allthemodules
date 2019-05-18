<?php

namespace Drupal\entity_serial\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FormatterBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'entity_serial_formatter' formatter.
 *
 * @FieldFormatter(
 *   id = "entity_serial_formatter",
 *   label = @Translation("Entity serial"),
 *   field_types = {
 *     "entity_serial_field_type"
 *   }
 * )
 */
class EntitySerialFormatter extends FormatterBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      // Implement default settings.
    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    return [
      // Implement settings form.
    ] + parent::settingsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $summary = [];
    // Implement settings summary.
    return $summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = [];

    foreach ($items as $delta => $item) {
      $elements[$delta] = ['#markup' => $this->viewValue($item)];
    }

    return $elements;
  }

  /**
   * Generate the output appropriate for one field item.
   *
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   One field item.
   *
   * @return string
   *   The textual output generated.
   */
  protected function viewValue(FieldItemInterface $item) {
    $fieldSettings = $item->getFieldDefinition()->getSettings();

    $entity = $item->getEntity();
    $entityId = $entity->id();
    $entityIdStart = (int) $fieldSettings['starts_with_entity_id'];
    $serialStart = (int) $fieldSettings['starts_with_id'];

    if ($entityId < $entityIdStart) {
      $result = 0;
    }
    else {
      $query = \Drupal::database()->select('entity_serial', 'es')
        ->fields('es', ['entity_id'])
        ->condition('entity_type_id', $entity->getEntityTypeId())
        ->condition('entity_bundle', $entity->bundle())
        ->condition('entity_id', $entityIdStart, '>=')
        ->condition('entity_id', $entityId, '<');
      $amountEntities = $query->countQuery()->execute()->fetchField();

      $result = $serialStart + $amountEntities;
    }
    return number_format($result, 0, '', '');
  }

}
