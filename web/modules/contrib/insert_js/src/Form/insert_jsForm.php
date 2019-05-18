<?php

namespace Drupal\insert_js\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;


class insert_jsForm extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'insert_js_setting';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'insert_js.settings',
    ];
  }

 


  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('insert_js.settings');
    $form['js_code'] = array(
      '#type' => 'textarea',
      '#title' => $this->t('adsense code'),
      '#default_value' => $config->get('insert_js.js_code'),
    );  
   
    return parent::buildForm($form, $form_state);
  }


 



  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('insert_js.settings')
      ->set('insert_js.js_code', $form_state->getValue('js_code'))     
      ->save();

     parent::submitForm($form, $form_state);    
  }


}

