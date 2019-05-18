<?php

namespace Drupal\one_time_link\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class LinkForm.
 *
 * @package Drupal\one_time_link\Form
 */
class LinkForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'one_time_link_link_form';
  }

  /**
   * To generate one time link in Config form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['uid'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => t('User Name or Email'),
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Generate'),
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $account = one_time_link_get_user_details($form_state->getValue('uid'));
    if(!$account) {
      $form_state->setErrorByName('uid', $this->t('Invalid User!!'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $account = one_time_link_get_user_details($form_state->getValue('uid'));

    $url = user_pass_reset_url($account);

    if ($url) {
      drupal_set_message('One Time Login Link =====>');
      drupal_set_message($url);
    }
  }

}
