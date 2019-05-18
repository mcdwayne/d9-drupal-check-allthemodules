<?php

namespace Drupal\closeblock\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a module settings form.
 */
class CloseBlockClearCookieForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'closeBlockClearCookieForm';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['resetCookie'] = [
      '#type' => 'submit',
      '#value' => $this->t('Reset Cookie'),
      '#description' => $this->t('Reset cookie to visible block'),
      '#id' => 'closeblock-clear-cookie-button',
    ];

    $form['#attached']['library'][] = 'closeblock/closeblock';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
