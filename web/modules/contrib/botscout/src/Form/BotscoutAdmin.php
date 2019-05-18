<?php

/**
 * @file
 * Contains \Drupal\botscout\Form\BotscoutAdmin.
 */

namespace Drupal\botscout\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class BotscoutAdmin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'botscout_admin';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('botscout.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);

    drupal_set_message($this->t('The configuration options have been saved.'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['botscout.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
  $form['botscout_ip'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable IP filtering'),
    '#default_value' => \Drupal::config('botscout.settings')->get('botscout_ip'),
    '#description' => t('Allows botscout to block bots based on the users IP 
address.'),
    '#required' => FALSE,
  );

  $form['botscout_name'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable username filtering'),
    '#default_value' => \Drupal::config('botscout.settings')->get('botscout_name'),
    '#description' => t('Allows botscout to block bots based on the users 
name.'),
    '#required' => FALSE,
  );

  $form['botscout_email'] = array(
    '#type' => 'checkbox',
    '#title' => t('Enable email filtering'),
    '#default_value' => \Drupal::config('botscout.settings')->get('botscout_email'),
    '#description' => t('Allows botscout to block bots based on the users email 
address.'),
    '#required' => FALSE,
  );

  $form['botscout_contact'] = array(
    '#type' => 'checkbox',
    '#title' => t('Protect the site-wide contact form'),
    '#default_value' => \Drupal::config('botscout.settings')->get('botscout_contact'),
    '#description' => t('enables botscout to check users when they submit a 
site-wide contact form.'),
    '#required' => FALSE,
  );

  $form['botscout_footer'] = array(
    '#type' => 'checkbox',
    '#title' => t('Show the BotScout footer on your site'),
    '#default_value' => \Drupal::config('botscout.settings')->get('botscout_footer'),
    '#description' => t('Shows a footer on your site that says "This site 
protected by BotScout"'),
    '#required' => FALSE,
  );

  $form['botscout_alert'] = array(
    '#type' => 'checkbox',
    '#title' => t('Alert by email when bot is blocked'),
    '#default_value' => \Drupal::config('botscout.settings')->get('botscout_alert'),
    '#description' => t('Sends an email to the site administrator. It will use 
the 
email set below'),
    '#required' => FALSE,
  );

  $form['botscout_adminemail'] = array(
    '#type' => 'textfield',
    '#size' => 35,
    '#title' => t('the email address to alert you at'),
    '#default_value' => \Drupal::config('botscout.settings')->get('botscout_adminemail'),
    '#description' => t('Sets the email for the alerts to go to when a bot is 
blocked'),
    '#required' => FALSE,
  );

  $form['botscout_key'] = array(
    '#type' => 'textfield',
    '#title' => t('Enter your API KEY'),
    '#size' => 25,
    '#default_value' => \Drupal::config('botscout.settings')->get('botscout_key'),
    '#description' => t('Entering an API key from botscout.net allows you to 
check 
unlimited registrations per day without one 20 is the limit per day please 
visit 
www.botscout.com for more info'),
    '#required' => FALSE,
  );

  $form['botscout_count'] = array(
    '#type' => 'textfield',
    '#title' => t('BotScout has stopped this many bots from submitting forms 
since 
you installed it'),
    '#size' => 25,
    '#description' => t('shows how many bots the BotScout has blocked on your 
site'),
    '#required' => FALSE,
    '#attributes' => array('readonly' => 'readonly'),
    '#default_value' => \Drupal::config('botscout.settings')->get('botscout_count'),
    '#disabled' => TRUE,
  );
  $form['actions']['#type'] = 'actions';
  $form['actions']['submit'] = array(
    '#type' => 'submit',
    '#value' => $this->t('Save configuration'),
    '#button_type' => 'primary',
  );

  // By default, render the form using theme_system_config_form().
  $form['#theme'] = 'system_config_form';

  return $form;
}
}
