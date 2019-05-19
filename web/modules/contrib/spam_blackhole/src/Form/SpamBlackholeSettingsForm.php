<?php

/**
 * @file
 * Contains \Drupal\spam_blackhole\Form\SpamBlackholeSettingsForm.
 */

namespace Drupal\spam_blackhole\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;

class SpamBlackholeSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'spam_blackhole_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = array();
    $form['spam_blackhole_domain'] = array(
      '#type' => 'fieldset',
      '#title' => t('Domain configuration'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['spam_blackhole_domain']['spam_blackhole_dummy_base_url'] = array(
      '#type' => 'textfield',
      '#title' => t('Dummy base URL for form submissions'),
      '#size' => 40,
      '#default_value' => \Drupal::config('spam_blackhole.settings')->get('spam_blackhole_dummy_base_url'),
      '#description' => t('The URL to be prepended to the relative URL in form action. Remember to use the protocol as part of the URL eg: http://www.example.com. Do not need a forward slash (/) in the end'),
    );
    $form['spam_blackhole_filter_options'] = array(
      '#type' => 'fieldset',
      '#title' => t('Form level filtering options'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $options = array(
      t('Apply on all forms.'),
      t('Apply on every form except the listed forms.'),
      t('Apply only on the listed forms.')
    );
    $description = t("Enter one form_id per line. You can use * as a wild character.");
    $form['spam_blackhole_filter_options']['spam_blackhole_filter_by_form'] = array(
      '#type' => 'radios',
      '#title' => t('Apply filters by form'),
      '#options' => $options,
      '#default_value' => \Drupal::config('spam_blackhole.settings')->get('spam_blackhole_filter_by_form'),
    );
    $form['spam_blackhole_filter_options']['spam_blackhole_filter_forms'] = array(
      '#type' => 'textarea',
      '#title' => t('Forms'),
      '#default_value' => \Drupal::config('spam_blackhole.settings')->get('spam_blackhole_filter_forms'),
      '#description' => $description,
    );
    $form['spam_blackhole_debug'] = array(
      '#type' => 'fieldset',
      '#title' => t('Debug options'),
      '#collapsible' => TRUE,
      '#collapsed' => TRUE,
    );
    $form['spam_blackhole_debug']['spam_blackhole_enable_debug'] = array(
      '#type' => 'checkbox',
      '#title' => t('Enable debug mode'),
      '#default_value' => \Drupal::config('spam_blackhole.settings')->get('spam_blackhole_enable_debug'),
      '#description' => t('Check this to enable debug information.'),
    );
    return parent::buildForm($form, $form_state);
  }
  
  /**
   * {@inheritdoc}
   */
   
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('spam_blackhole.settings')
         ->set('spam_blackhole_dummy_base_url', $form_state->getValue('spam_blackhole_dummy_base_url'))
         ->set('spam_blackhole_filter_by_form', $form_state->getValue('spam_blackhole_filter_by_form'))
         ->set('spam_blackhole_filter_forms', $form_state->getValue('spam_blackhole_filter_forms'))
         ->set('spam_blackhole_enable_debug', $form_state->getValue('spam_blackhole_enable_debug'))
         ->save();
    parent::submitForm($form, $form_state);
  }  
}
