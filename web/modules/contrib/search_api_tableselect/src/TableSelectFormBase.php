<?php

namespace Drupal\search_api_tableselect;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class TableSelectFormBase.
 *
 * @package Drupal\search_api_tableselect
 */
abstract class TableSelectFormBase extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, array $variables = []) {
    $form = array_merge($variables, $form);
    $form['#theme'] = 'views_view_tableselect_form';

    $form['operation'] = [
      '#type' => 'select',
      '#title' => $this->t('Choose operation'),
      '#options' => [
        'status' => $this->t('Status'),
        'warning' => $this->t('Warning'),
      ],
    ];

    $items = ['#tree' => TRUE];
    foreach ($form['#rows'] as $delta => $row) {
      $item_id = $row->search_api_id;
      $items[$item_id] = [
        'item_id' => ['#type' => 'checkbox'],
      ];
    }
    $form['items'] = $items;

    $form['actions'] = [
      '#type' => 'container',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Find out what was submitted.
    $values = $form_state->getValue('items');
    $operation = $form_state->getValue('operation');

    foreach ($values as $id => $value) {
      $options = [
        '%item_id' => $id,
        '%item_value' => intval($value['item_id']),
      ];
      drupal_set_message($this->t('Item %item_id is set to %item_value', $options), $operation);

    }
  }

}
