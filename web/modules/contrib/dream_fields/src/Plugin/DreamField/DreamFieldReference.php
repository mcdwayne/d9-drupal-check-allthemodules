<?php

namespace Drupal\dream_fields\Plugin\DreamField;

use Drupal\dream_fields\DreamFieldPluginBase;
use Drupal\dream_fields\FieldBuilderInterface;

/**
 * Plugin implementation of 'entity reference'.
 *
 * @DreamField(
 *   id = "entity_reference",
 *   label = @Translation("Reference"),
 *   description = @Translation("This will add an input field for an entity reference using auto complete and will be outputted with a link."),
 *   preview = "images/textfield-dreamfields.png",
 *   field_types = {
 *     "entity_reference"
 *   },
 * )
 */
class DreamFieldReference extends DreamFieldPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getForm() {
    $form = [];
    $form['target_type'] = [
      '#type' => 'select',
      '#title' => t('Type of item to reference'),
      '#options' => \Drupal::entityManager()->getEntityTypeLabels(TRUE),
      '#size' => 1,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function saveForm($values, FieldBuilderInterface $field_builder) {
    $field_builder
      ->setField('entity_reference', [
        'target_type' => $values['target_type'],
      ], [
        'link_to_entity' => TRUE,
        'handler' => 'default',
        'handler_settings' => [
          'target_bundles' => [],
        ],
      ])
      ->setWidget('entity_reference_autocomplete');
  }

}
