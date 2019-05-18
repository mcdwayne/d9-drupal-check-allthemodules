<?php

namespace Drupal\encrypt_content_client\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for updating ECC keys, public in database and private on client-side.
 */
class UpdateKeysBlockForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'encrypt_content_client_update_keys_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['private_key'] = [
      '#type' => 'textfield',
      '#title' => t('ECC Private Key'),
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => t('Save key'),
    ];
    
    $form['#attached']['library'][] = 'encrypt_content_client/manage_ecc_keys';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
