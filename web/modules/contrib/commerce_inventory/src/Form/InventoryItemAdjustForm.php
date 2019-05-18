<?php

namespace Drupal\commerce_inventory\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for Inventory Item adjustment forms.
 *
 * @ingroup commerce_inventory
 */
class InventoryItemAdjustForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['adjustment-field'] = [
      '#type' => 'commerce_inventory_adjustment',
      '#default_value' => [
        'item' => $this->entity,
        'type' => 'manual',
      ],
      '#hidden_fields' => [
        'item',
        // 'related_item',.
      ],
      '#title_format' => 'sentence',
      '#field_title_format' => NULL,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  protected function actions(array $form, FormStateInterface $form_state) {
    return [];
  }

}
