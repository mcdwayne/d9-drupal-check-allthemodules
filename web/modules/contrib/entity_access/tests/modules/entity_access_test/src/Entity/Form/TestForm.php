<?php

namespace Drupal\entity_access_test\Entity\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Trait TestForm.
 */
trait TestForm {

  /**
   * {@inheritdoc}
   */
  final protected function elements(array $form, FormStateInterface $form_state, $node_type) {
    $form['description'] = [
      '#markup' => $this->t('Local task for "@node_type" content type.', [
        '@node_type' => $node_type,
      ]),
    ];

    $form['test_field'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Test field'),
      '#required' => TRUE,
      '#disabled' => $form_state->isRebuilding(),
      '#default_value' => $form_state->getValue('test_field'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  final public function actions(array $form, FormStateInterface $form_state) {
    $actions = [];

    $actions['test_action'] = [
      '#type' => 'submit',
      '#value' => $form_state->isRebuilding() ? $this->t('Confirm') : $this->t('Go'),
      '#submit' => [$form_state->isRebuilding() ? '::submitForm' : '::needsRebuild'],
    ];

    return $actions;
  }

  /**
   * {@inheritdoc}
   */
  final public function needsRebuild(array &$form, FormStateInterface $form_state) {
    $form_state->setRebuild(TRUE);
  }

  /**
   * {@inheritdoc}
   */
  final public function submitForm(array &$form, FormStateInterface $form_state) {
    drupal_set_message($this->t('You have successfully submitted the value: @value', [
      '@value' => $form_state->getValue('test_field'),
    ]));
  }

}
