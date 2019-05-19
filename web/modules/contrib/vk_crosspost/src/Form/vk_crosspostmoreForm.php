<?php

namespace Drupal\vk_crosspost\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

use Drupal\Core\Render\Element\Button;

use Drupal\Core\Routing\TrustedRedirectResponse;


class vk_crosspostmoreForm extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vk_crosspost_formore_setting';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vk_crosspost.settings',
    ];
  }
 public function buildForm(array $form, FormStateInterface $form_state) {


    $config = $this->config('vk_crosspost.settings');

  


    $form['token'] = array(
   //   '#type' => 'textfield',
      '#title' => $this->t('token from vk'),
    //  '#default_value' => 'empty',
    );  




    return parent::buildForm($form, $form_state);
  }


 



  /** 
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
   

    // Retrieve the configuration
    $this->config('vk_crosspost.settings')
      // Set the submitted configuration setting
      ->set('vk_crosspost.token', '')     
      ->save();

     parent::submitForm($form, $form_state);

define("SCOPE",     "offline,wall");      

$client_id = \Drupal::config('vk_crosspost.settings')->get('vk_crosspost.app');

$vk_url = 
  'https://api.vk.com/oauth/authorize?client_id=' .
  $client_id .
  '&scope=' .
  SCOPE .
  '&redirect_uri=https://api.vk.com/blank.html&display=page&response_type=token' ;


//редирект на vk.
     $probe_url = new TrustedRedirectResponse($vk_url);
      $form_state->setResponse($probe_url);

}
}
