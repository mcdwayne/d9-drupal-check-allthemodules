<?php

namespace Drupal\nginx_cache_clear\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form to set the configuration for nginx cache clear.
 *
 * @category Custom_Form.
 * @package Nginx Cache Clear
 * @author chippy <chippy.96twc.zyxware@gmail.com>
 */
class NginxSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'system.site',
    ];
  }

  /**
   * Function to get the formid.
   *
   * @return Id
   *     Id of nginx cache clear configuration form.
   */
  public function getFormId() {
    return 'ngnix_settings_form';
  }

  /**
   * {@inheritdoc}
   *
   * Form to set the configuration for nginx cache clear.
   *
   * @param array $form
   *    Form elements.
   * @param FormStateInterface $form_state
   *    The form states.
   *
   * @return form
   *    Return the form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form['server_cache_config'] = array(
      '#type' => 'fieldset',
      '#title' => t('Server Cache Configuration'),
    );

    $form['server_cache_config']['server_cache_key'] = array(
      '#type' => 'textfield',
      '#title' => t('Webserver cache key'),
      '#required' => TRUE,
      '#default_value' => \Drupal::config('nginx_cache_clear.settings')->get('server_cache_key'),
      '#description' => t('Please check the server configuration and confirm the hash key used. Key Format : $scheme$request_method$host$request_uri$is_args$args.'),
    );

    $form['server_cache_config']['server_cache_path'] = array(
      '#type' => 'textfield',
      '#title' => t('Webserver cache file path'),
      '#required' => TRUE,
      '#default_value' => \Drupal::config('nginx_cache_clear.settings')->get('server_cache_path'),
      '#description' => t('Please check the server configuration and enter the path configured to store cache file.'),
    );

    $form['server_cache_config']['server_cache_auto_delete'] = array(
      '#type' => 'checkbox',
      '#title' => t('Auto clear cache when content edit'),
      '#required' => FALSE,
      '#default_value' => \Drupal::config('nginx_cache_clear.settings')->get('server_cache_auto_delete'),
      '#description' => t('If this option is enabled, then the cache file of content(URL alias) will be deleted when the user update the content.'),
    );

    return parent::buildForm($form, $form_state);;
  }

  /**
   * Validate function.
   *
   * @param array &$form
   *   Form elements.
   * @param FormStateInterface $form_state
   *    The form states.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * Function to save the configuration settings to system_variables.
   *
   * @param array &$form
   *   Form elements.
   * @param FormStateInterface $form_state
   *    The form states.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);
    \Drupal::configFactory()->getEditable('nginx_cache_clear.settings')
      ->set('server_cache_key', $form_state->getValue('server_cache_key'))
      ->set('server_cache_path', $form_state->getValue('server_cache_path'))
      ->set('server_cache_auto_delete', $form_state->getValue('server_cache_auto_delete'))
      ->save();
  }

}
