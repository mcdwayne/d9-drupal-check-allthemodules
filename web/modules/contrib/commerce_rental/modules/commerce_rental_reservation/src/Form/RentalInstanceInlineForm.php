<?php

namespace Drupal\commerce_rental_reservation\Form;

use Drupal\inline_entity_form\Form\EntityInlineForm;

/**
 * Defines the inline form for rental variations.
 */
class RentalInstanceInlineForm extends EntityInlineForm {

  /**
   * {@inheritdoc}
   */
  public function getEntityTypeLabels() {
    $labels = [
      'singular' => t('instance'),
      'plural' => t('instances'),
    ];
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function getTableFields($bundles) {
    $fields = parent::getTableFields($bundles);
    $fields['serial'] = [
      'type' => 'field',
      'label' => t('Serial Number'),
    ];
    $fields['state'] = [
      'type' => 'field',
      'label' => t('State'),
      'weight' => 99,
      'display_options' => [
        'type' => 'entity_reference_label',
        'settings' => ['link' => FALSE],
      ],
    ];
    $fields['label']['label'] = t('Title');
    return $fields;
  }
}
