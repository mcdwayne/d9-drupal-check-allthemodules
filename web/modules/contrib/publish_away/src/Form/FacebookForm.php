<?php

/**
 * @file
 * Contains \Drupal\publish_away\Form\FacebookForm.
 */
 
namespace Drupal\publish_away\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\publish_away\Controller\FacebookController as FacebookController;

 
class FacebookForm extends ConfigFormBase {
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'Facebook_settings_api';
  }
  
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $access = \Drupal::currentUser()->hasPermission('administer publish away');
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
      $facebook_conf = $this->config('publish_away.facebook');
      foreach ($facebook as $key => $value) {
        $facebook_conf->set("facebook.{$key}", $value);
      }
      $facebook_conf->save();
    }
    parent::submitForm($form, $form_state);
  }
  
}
