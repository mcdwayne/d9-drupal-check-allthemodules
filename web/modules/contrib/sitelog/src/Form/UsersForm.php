<?php

namespace Drupal\sitelog\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller.
 */
class UsersForm extends FormBase {

  /**
   * Form builder.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['accounts'] = array(
      '#type' => 'radios',
      '#title' => t('Accounts'),
      '#options' => array(
        'active' => t('active'),
        'inactive' => t('inactive'),
        'registrations' => t('registrations'),
        'accessed' => t('accessed'),
      ),
      '#default_value' => 'active',
    );
    return $form;
  }

  /**
   * Form identifier getter method.
   */
  public function getFormId() {}

  /**
   * Submit handler.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {}
}
