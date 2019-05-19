<?php

/**
 * @file
 * Contains \Drupal\sjisocialconnect\Form\TwitterForm.
 */
 
namespace Drupal\sjisocialconnect\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\sjisocialconnect\Controller\TwitterController as TwitterController;

 
class TwitterForm extends ConfigFormBase {
  
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'sjisocialconnect.twitter_pub',
    ];
  }
  
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'Twitter_settings_api';
  }
  
  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $default_values = NULL) {
    $access = \Drupal::currentUser()->hasPermission('administer sji social connect');
    $form = TwitterController::formElement();
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
    if ($form_state->hasValue('twitter')) {
      $twitter = $form_state->getValue('twitter');
      if (trim($twitter['consumer_key']) != '' && trim($twitter['consumer_secret']) == '') {
        $form_state->setErrorByName('twitter][consumer_secret', $this->t('Twitter consumer secret key is missing.'));
      }
      if (trim($twitter['consumer_key']) == '' && trim($twitter['consumer_secret']) != '') {
        $form_state->setErrorByName('twitter][consumer_key', $this->t('Twitter consumer key is missing.'));
      }
      if (trim($twitter['consumer_key']) != '' && trim($twitter['oauth_token']) == '') {
        $form_state->setErrorByName('twitter][oauth_token', $this->t('Twitter access token is missing.'));
      }
      if (trim($twitter['consumer_key']) != '' && trim($twitter['oauth_token_secret']) == '') {
        $form_state->setErrorByName('twitter][oauth_token_secret', $this->t('Twitter access token secret is missing.'));
      }
    }
    parent::validateForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $twitter = $form_state->getValue('twitter');
    if (!empty($twitter)) {
      $twitter_conf = $this->config('sjisocialconnect.twitter_pub');
      foreach ($twitter as $key => $value) {
        $twitter_conf->set("twitter.{$key}", $value);
      }
      $twitter_conf->save();
    }
    parent::submitForm($form, $form_state);
  }
  
}
