<?php

/**
 * @file
 * Contains \Drupal\sjisocialconnect\Form\FacebookForm.
 */
 
namespace Drupal\sjisocialconnect\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\sjisocialconnect\Controller\FacebookController as FacebookController;

 
class FacebookForm extends ConfigFormBase {
 
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sjisocialconnect.facebook_pub',
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'Facebook_settings_api';
  }
  
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $default_values = NULL) {
    $access = \Drupal::currentUser()->hasPermission('administer sji social connect');
    $form = FacebookController::formElement();
    $form['#tree'] = TRUE;
    $form['actions'] = array('#type' => 'actions', '#access' => $access);
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save'),
    );
    
    return $form;
  }
  
  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    if ($form_state->hasValue('facebook')) {
      $facebook = $form_state->getValue('facebook');
      if (trim($facebook['appId']) == '' && trim($facebook['appSecret']) != '') {
        $form_state->setErrorByName('facebook][appId', $this->t('Facebook appId is missing.'));
      }
      if (trim($facebook['appId']) != '' && trim($facebook['appSecret']) == '') {
        $form_state->setErrorByName('facebook][appSecret', $this->t('Facebook appSecret key is missing.'));
      }
      if (trim($facebook['page_id']) != '' && (trim($facebook['appSecret']) == '' || trim($facebook['appId']) == '')) {
        $form_state->setErrorByName('facebook][appId', $this->t('Facebook appId is missing.'));
        $form_state->setErrorByName('facebook][appSecret', $this->t('Facebook appSecret key is missing.'));
      }
    }
    parent::validateForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $facebook = $form_state->getValue('facebook');
    if (!empty($facebook)) {
      $facebook_conf = $this->config('sjisocialconnect.facebook_pub');   
      foreach ($facebook as $key => $value) {
        $facebook_conf->set("facebook.{$key}", $value);
      }
      $facebook_conf->save();
    }
    parent::submitForm($form, $form_state);
  }
  
}
