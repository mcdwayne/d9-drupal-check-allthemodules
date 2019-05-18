<?php

namespace Drupal\m4032404\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class M4032404Form.
 *
 * @package Drupal\m4032404\Form
 */
class M4032404Form extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'm4032404_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $m4032404_admin_only = $this->config('m4032404.settings')->get('admin_only');

    $form['m4032404_admin_only'] = array(
      '#type' => 'checkbox',
      '#title' => $this->t('Enforce on Admin Only'),
      '#description' => $this->t('Check the box to enforce the 404 behavior only on admin paths'),
      '#default_value' => $m4032404_admin_only,
    );

    $form['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
    );

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('m4032404.settings')
      ->set('admin_only', $form_state->getValue('m4032404_admin_only'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getEditableConfigNames() {
    return ['m4032404.settings'];
  }

}
