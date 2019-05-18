<?php

namespace Drupal\new_relic_rpm\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Render\Element;

/**
 * Provides a settings form to configure the New Relic RPM module.
 */
class NewRelicRpmSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'new_relic_rpm_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['new_relic_rpm.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = [];

    $form['track_drush'] = [
      '#type' => 'select',
      '#title' => t('Drush transactions'),
      '#description' => t('How do you wish RPM to track drush commands?'),
      '#options' => [
        'ignore' => t('Ignore completely'),
        'bg' => t('Track as background tasks'),
        'norm' => t('Track normally'),
      ],
      '#default_value' => \Drupal::config('new_relic_rpm.settings')->get('track_drush'),
    ];

    $form['track_cron'] = [
      '#type' => 'select',
      '#title' => t('Cron transactions'),
      '#description' => t('How do you wish RPM to track cron tasks?'),
      '#options' => [
        'ignore' => t('Ignore completely'),
        'bg' => t('Track as background tasks'),
        'norm' => t('Track normally'),
      ],
      '#default_value' => \Drupal::config('new_relic_rpm.settings')->get('track_cron'),
    ];

    $form['module_deployment'] = [
      '#type' => 'select',
      '#title' => t('Track module activation as deployment'),
      '#description' => t('Turning this on will create a "deployment" on the New Relic RPM dashboard each time a module is installed or uninstalled. This will help you track before and after statistics.'),
      '#options' => [
        1 => t('Enable'),
        0 => t('Disable'),
      ],
      '#default_value' => (int) \Drupal::config('new_relic_rpm.settings')->get('module_deployment'),
    ];

    $form['config_import'] = [
      '#type' => 'select',
      '#title' => t('Track configuration imports as deployment'),
      '#description' => t('Turning this on will create a "deployment" on the New Relic RPM dashboard each time a set of configuration is imported. This will help you track before and after statistics.'),
      '#options' => [
        1 => t('Enable'),
        0 => t('Disable'),
      ],
      '#default_value' => (int) \Drupal::config('new_relic_rpm.settings')->get('config_import'),
    ];

    $roles = user_role_names();
    $form['ignore_roles'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => t('Ignore Roles'),
      '#description' => t('Select roles that you wish to be ignored on the New Relic RPM dashboards. Any user with at least one of the selected roles will be ignored.'),
      '#options' => $roles,
      '#default_value' => \Drupal::config('new_relic_rpm.settings')->get('ignore_roles'),
    ];

    $form['ignore_urls'] = [
      '#type' => 'textarea',
      '#wysiwyg' => FALSE,
      '#title' => t('Ignore URLs'),
      '#description' => t('Enter URLs you wish New Relic RPM to ignore. Enter one URL per line.'),
      '#default_value' => \Drupal::config('new_relic_rpm.settings')->get('ignore_urls'),
    ];

    $form['bg_urls'] = [
      '#type' => 'textarea',
      '#wysiwyg' => FALSE,
      '#title' => t('Background URLs'),
      '#description' => t('Enter URLs you wish to have tracked as background tasks. Enter one URL per line.'),
      '#default_value' => \Drupal::config('new_relic_rpm.settings')->get('bg_urls'),
    ];

    $form['exclusive_urls'] = [
      '#type' => 'textarea',
      '#wysiwyg' => FALSE,
      '#title' => t('Exclusive URLs'),
      '#description' => t('Enter URLs you wish to exclusively track. This is useful for debugging specific issues. **NOTE** Entering URLs here effectively marks all other URLs as ignored. Leave blank to disable.'),
      '#default_value' => \Drupal::config('new_relic_rpm.settings')->get('exclusive_urls'),
    ];

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('API Key'),
      '#description' => t('Enter your New Relic API key if you wish to view reports and analysis within Drupal.'),
      '#default_value' => \Drupal::config('new_relic_rpm.settings')->get('api_key'),
    ];

    $form['watchdog_severities'] = [
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => t('Forward watchdog messages'),
      '#description' => t('Select which watchdog severities should be forwarded to New Relic API as errors.'),
      '#options' => RfcLogLevel::getLevels(),
      '#default_value' => \Drupal::config('new_relic_rpm.settings')->get('watchdog_severities'),
    ];

    $form['override_exception_handler'] = [
      '#type' => 'checkbox',
      '#title' => t('Override exception handler'),
      '#description' => t('Check to override default Drupal exception handler and to have exceptions passed to New Relic.'),
      '#default_value' => \Drupal::config('new_relic_rpm.settings')->get('override_exception_handler'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('new_relic_rpm.settings');

    foreach (Element::children($form) as $variable) {
      $config->set($variable, $form_state->getValue($form[$variable]['#parents']));
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

}
