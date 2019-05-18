<?php

namespace Drupal\get_a_quote\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides the QuoteGeneralSettingsForm form.
 */
class QuoteGeneralSettingsForm extends ConfigFormBase {

  /**  
   * {@inheritdoc}  
   */  
  protected function getEditableConfigNames() {  
    return [  
      'get_a_quote.settings',  
    ];  
  }  

  /**  
   * {@inheritdoc}  
   */  
  public function getFormId() {  
    return 'get_quote_settings_form';  
  }  
  
  /**  
   * {@inheritdoc}  
   */  
  public function buildForm(array $form, FormStateInterface $form_state) {  
    $config = $this->config('get_a_quote.settings');  

    $form['enable_quote'] = [  
      '#type' => 'checkbox',  
      '#title' => $this->t('Enable Get a Quote for commerce products'),  
      '#description' => $this->t('Get a Quote button will be displayed on commerce Review page.'),  
      '#default_value' => $config->get('enable_quote'),  
    ];  

    return parent::buildForm($form, $form_state);  
  }  
  
  /**  
   * {@inheritdoc}  
   */  
  public function submitForm(array &$form, FormStateInterface $form_state) {  
    parent::submitForm($form, $form_state);  

    $this->config('get_a_quote.settings')  
      ->set('enable_quote', $form_state->getValue('enable_quote'))  
      ->save();  
  }  
}
