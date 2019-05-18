<?php
namespace Drupal\commerce_replace_order\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\core\Form\FormStateInterface;

/**
 * Provides Configuration Page for the module commerce_replace_order.
 */
class CommerceReplaceOrderConfigForm extends ConfigFormBase {

/**
 * Provides Configuration Page name for Accessing the values
 */
  protected function getEditableConfigNames() {
    return [
    'commerce_replace_order.config',
    ];
  }

/**
 * Provides Configuration Form name
 */
  public function getFormId() {
    return 'commerce_replace_order_config';
  }

/**
 * Creates a Form for Configuring the Module
 */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('commerce_replace_order.config');
    $form['checkbox_mail'] = [
        '#type'    => 'checkbox',
        '#default_value' => $config->get('checkbox_mail') ? $config
        ->get('checkbox_mail') : 0,
        '#title'   => t('Want to Recieve mail For user reordering the product 
          Which is not available.')
    ];
    $form['email'] = [
        '#type'          => 'email',
        '#title'         => t('Email Id'),
        '#default_value' => $config->get('email') ? $config->get('email'): '',
        '#states'        => [
          'visible'      => [
            ':input[name="checkbox_mail"]' => ['checked' => TRUE],
          ],
        ],
    ];
    return parent::buildForm($form, $form_state);
  }

/**
 * Validates the Configuration Form
 */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if($form_state->getValue('checkbox_mail') == '1') {
      if(empty($form_state->getValue('email'))) {
        $form_state->setErrorByName('email', $this->t('Enter Your Email Id'));
      }
    }
  }

/**
 * Submits the Configuration Form
 */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    $config = $this->config('commerce_replace_order.config');
    $config->set('checkbox_mail', $form_state->getValue('checkbox_mail'));
    if($form_state->hasValue('email')){
      $config->set('email', $form_state->getValue('email'));
    }
    $config->save();
  }
}