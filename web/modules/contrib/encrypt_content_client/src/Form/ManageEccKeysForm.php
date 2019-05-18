<?php

namespace Drupal\encrypt_content_client\Form;

use Drupal\user\Entity\User;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form that uses JavaScript library for ECC keys generation.
 */
class ManageEccKeysForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'encrypt_content_client_generate_keys_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $user = User::load(\Drupal::currentUser()->id());
    
    $form['user_public_key'] = [
      '#title' => t('ECC public key - server'),
      '#type' => 'textfield',
      '#value' => $user->field_public_key->value,
    ];
    
    $form['user_private_key'] = [
      '#title' => t('ECC private key - local'),
      '#type' => 'textfield',
      '#value' => "",
    ];

    $form['save_ecc_keys'] = [
      '#type' => 'submit',
      '#value' => t('Update encryption keys'),
    ];

    $form['cancel'] = [
      '#type' => 'markup',
      '#value' => ''
    ];

    // Attach JavaScript libraries. 
    $form['#attached']['library'][] = 'encrypt_content_client/manage_ecc_keys_js';
    $form['#attached']['library'][] = 'encrypt_content_client/filesaver_js';

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
    $user = User::load(\Drupal::currentUser()->id());
  }

}
