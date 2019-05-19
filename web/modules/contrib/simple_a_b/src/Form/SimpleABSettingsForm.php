<?php

namespace Drupal\simple_a_b\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Defines a form that configures simple_a_b settings.
 */
class SimpleABSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'simple_a_b_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'simple_a_b.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $simple_a_b_config = $this->config('simple_a_b.settings');

    // Create holder for reporting settings.
    $form['report'] = [
      '#type' => 'details',
      '#title' => t('Reporting'),
      '#description' => t('Settings to configure how simple a/b handles reporting.'),
      '#open' => TRUE,
    ];

    // Reporting methods.
    $reportingMethods = static::getReportingMethods();
    $form['report']['reporting'] = [
      '#type' => 'select',
      '#title' => $this->t('Reporting method'),
      '#options' => $reportingMethods['options'],
      '#default_value' => $simple_a_b_config->get('reporting'),
      '#description' => $reportingMethods['description'],
    ];

    // Create holder for cookie session settings.
    $form['cookie_session'] = [
      '#type' => 'details',
      '#title' => t('Cookies & Sessions'),
      '#description' => t('Settings to configure how, if at all, the simple a/b module remembers variations.'),
      '#open' => TRUE,
    ];

    // Create drop down of options for remember.
    $rememberMethods = static::getRememberMethod();
    $form['cookie_session']['remember'] = [
      '#type' => 'select',
      '#title' => $this->t('Remember method'),
      '#options' => $rememberMethods,
      '#default_value' => $simple_a_b_config->get('remember'),
      '#description' => t('Select the method you wish for simple a/b to remember variations.'),
    ];

    // Text field for remember prefix.
    $form['cookie_session']['remember_prefix'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Prefix'),
      '#size' => 11,
      '#maxlength' => 10,
      '#default_value' => $simple_a_b_config->get('remember_prefix'),
      '#description' => t('Prefix to be attached to the front of cookie/session data.'),
    ];

    // Select for the length of time the cookie / session lives.
    $rememberLifetime = static::getRememberLifetime();
    $form['cookie_session']['remember_lifetime'] = [
      '#type' => 'select',
      '#title' => $this->t('Lifetime'),
      '#options' => $rememberLifetime,
      '#default_value' => $simple_a_b_config->get('remember_lifetime'),
      '#description' => t('The amount of time the variation will be remembered.'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $this->config('simple_a_b.settings')
      ->set('reporting', $values['reporting'])
      ->set('remember', $values['remember'])
      ->set('remember_prefix', $values['remember_prefix'])
      ->set('remember_lifetime', $values['remember_lifetime'])
      ->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Looks up any reporting methods installed.
   *
   * @return array
   *   Returns the reporting methods
   */
  protected static function getReportingMethods() {
    $output = [];
    $options = [];

    // Default of none.
    $options['_none'] = t('- none -');

    $manager = \Drupal::service('plugin.manager.simpleab.report');
    $plugins = $manager->getDefinitions();

    // If we have some plugin's, loop them to create a drop down list of items.
    if (!empty($plugins)) {
      foreach ($plugins as $reporter) {
        $instance = $manager->createInstance($reporter['id']);
        $options[$instance->getId()] = $instance->getName();
      }
    }

    // Add the options to the array.
    $output['options'] = $options;

    // Check if with have any options, if not display module link.
    if (count($options) > 1) {
      $output['description'] = t('Where should the results be reported to?');
    }
    else {
      $module_path = '/admin/modules';
      $output['description'] = t('No reporting methods could be found. Please <a href="@simple-ab-modules">enable</a> at least one.', ['@simple-ab-modules' => $module_path]);
    }

    return $output;
  }

  /**
   * Creates a list of remembering method options.
   *
   * @return array
   *   Returns remember methods
   */
  protected static function getRememberMethod() {
    $options = [];

    // Default of none.
    $options['_none'] = t('- none -');
    // Second option cookies.
    $options['cookie'] = t('Cookies');

    return $options;
  }

  /**
   * Creates a list of lifetime options.
   *
   * @return array
   *   Returns a list of lifetime options
   */
  protected static function getRememberLifetime() {
    $options = [];

    $options['60'] = t('1 minute');
    $options['300'] = t('5 minutes');
    $options['1800'] = t('30 minutes');
    $options['3600'] = t('1 hour');
    $options['7200'] = t('2 hours');
    $options['86400'] = t('1 day');
    $options['604800'] = t('7 days');
    $options['2592000'] = t('30 days');

    return $options;
  }

}
