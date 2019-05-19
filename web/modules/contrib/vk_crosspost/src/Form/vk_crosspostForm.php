<?php

namespace Drupal\vk_crosspost\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

//use Drupal\Core\Url;
use Drupal\Core\Routing\TrustedRedirectResponse;

//для возврата с vk на сайт
use Drupal\Core\Url;

/**
 * Configure vk_crosspost settings for this site.
 */
class vk_crosspostForm extends ConfigFormBase {
  /** 
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vk_crosspost_for_setting';
  }

  /** 
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'vk_crosspost.settings',
    ];
  }

 


  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('vk_crosspost.settings');

    $form['app'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('id app'),
      '#default_value' => $config->get('vk_crosspost.app'),
    );  

     $form['id_user'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('id user'),
      '#default_value' => $config->get('vk_crosspost.id_user'),
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
      ->set('vk_crosspost.app', $form_state->getValue('app'))
      ->set('vk_crosspost.id_user', $form_state->getValue('id_user'))     
      ->save();

     parent::submitForm($form, $form_state);
     
 /*
  
define("SCOPE",     "offline,wall");      

$client_id = \Drupal::config('vk_crosspost.settings')->get('vk_crosspost.group');

$vk_url = 
  'https://api.vk.com/oauth/authorize?client_id=' .
  $client_id .
  '&scope=' .
  SCOPE .
  '&redirect_uri=https://api.vk.com/blank.html&display=page&response_type=token' ;


//редирект на гугл.
     $probe_url = new TrustedRedirectResponse($vk_url);
      $form_state->setResponse($probe_url);

*/

     
  }
}

