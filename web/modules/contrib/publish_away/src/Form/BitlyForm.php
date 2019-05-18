<?php

/**
 * @file
 * Contains \Drupal\publish_away\Form\BitlyForm.
 */
 
namespace Drupal\publish_away\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\publish_away\Controller\BitlyController as BitlyController;

 
class BitlyForm extends ConfigFormBase {
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'Bitly_settings_api';
  }
  
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $default_values = NULL) {
    $access = \Drupal::currentUser()->hasPermission('administer publish away');
    $form += BitlyController::formElement();
    $form['#tree'] = TRUE;
    $form['actions'] = array('#type' => 'actions', '#access' => $access);
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => t('Save'),
    );
    
    return $form;
  }
  
  /**
   * Implements \Drupal\Core\Form\FormInterface::validateForm().
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $form_state->cleanValues();
    if ($form_state->hasValue('bitly')) {
      $bitly = $form_state->getValue('bitly');
      if (trim($bitly['username']) == '' && trim($bitly['apikey']) != '') {
        $form_state->setErrorByName('bitly][username', t('Bitly username is missing.'));
      }
      if (trim($bitly['username']) != '' && trim($bitly['apikey']) == '') {
        $form_state->setErrorByName('bitly][apikey', t('Bitly API Key is missing.'));
      }
    }
    parent::validateForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $bitly = $form_state->getValue('bitly');
    if (!empty($bitly)) {
      $bitly_conf = $this->config('publish_away.bitly');
      foreach ($bitly as $key => $value) {
        $bitly_conf->set("bitly.{$key}", $value);
      }
      $bitly_conf->save();
    }
    parent::submitForm($form, $form_state);
  }
  
}
