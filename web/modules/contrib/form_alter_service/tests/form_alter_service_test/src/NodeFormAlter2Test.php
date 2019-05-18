<?php

namespace Drupal\form_alter_service_test;

use Drupal\Core\Form\FormStateInterface;
use Drupal\form_alter_service\FormAlterBase;

/**
 * {@inheritdoc}
 */
class NodeFormAlter2Test extends FormAlterBase {

  /**
   * {@inheritdoc}
   */
  public function hasMatch(array $form, FormStateInterface $form_state, string $form_id) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    $form['dummy'] = [
      '#markup' => 'NOBODY CAN STOP ME!',
    ];
  }

}
