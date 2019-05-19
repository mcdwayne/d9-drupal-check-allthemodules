<?php

namespace Drupal\simple_analytics\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\simple_analytics\SimpleAnalyticsHelper;

/**
 * Simple Analytics Configuration form.
 */
class SimpleAnalyticsConfig extends ConfigFormBase {

  /**
   * Get Form ID.
   */
  public function getFormId() {
    return 'simple_analytics_form';
  }

  /**
   * Build Form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = parent::buildForm($form, $form_state);

    $config = SimpleAnalyticsHelper::getConfig();
    $durations = [
      7 => '1 Week',
      30 => '1 Month',
      60 => '2 Months',
      180 => '6 Months',
      365 => '1 Year',
    ];

    $form['google'] = [
      '#type' => 'details',
      '#title' => $this->t("Google Analytics"),
      '#open' => $config->get('google-id') ? TRUE : FALSE,
    ];
    $form['google']['google-id'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Google Analytics ID"),
      '#default_value' => $config->get('google-id'),
    ];

    $form['piwik'] = [
      '#type' => 'details',
      '#title' => $this->t("Matomo (Piwik)"),
      '#description' => $this->t("Matomo is the new name of Piwik Analytics"),
      '#open' => $config->get('piwik-uri') ? TRUE : FALSE,
    ];
    $form['piwik']['piwik-uri'] = [
      '#type' => 'textfield',
      '#description' => $this->t('Example : http://www.example.com/piwik'),
      '#title' => $this->t("Matomo (Piwik Analytics) URL"),
      '#default_value' => $config->get('piwik-uri') ? "http:" . $config->get('piwik-uri') : "",
    ];
    $form['piwik']['piwik-id'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Matomo (Piwik Analytics) ID"),
      '#default_value' => $config->get('piwik-id'),
      '#states' => [
        'invisible' => [
          ':input[id="edit-piwik-uri"]' => ['value' => ''],
        ],
      ],
    ];

    // Custom tracker.
    $form['custom'] = [
      '#type' => 'details',
      '#title' => $this->t("Custom"),
      '#open' => $config->get('custom') ? TRUE : FALSE,
    ];
    $form['custom']['custom'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Custom Script"),
      '#description' => $this->t("The code must contains 'script' tag. Ex : @example", ['@example' => '<script type="text/javascript">....']),
      '#default_value' => $config->get('custom'),
    ];
    $form['custom']['custom-noscript'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Custom No Script tracking code"),
      '#description' => $this->t("The code must contains 'noscript' tag. Ex : @example", ['@example' => '<noscript>....']),
      '#default_value' => $config->get('custom-noscript'),
    ];

    // Internal tracker (standalone).
    $form['sa_track'] = [
      '#type' => 'details',
      '#title' => $this->t("Internal tracker"),
      '#open' => $config->get('sa_tracker'),
    ];
    $form['sa_track']['sa_tracker'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Use Simple Analytics internal tracker"),
      '#default_value' => $config->get('sa_tracker'),
    ];
    $form['sa_track']['sa_tracker_server'] = [
      '#type' => 'select',
      '#title' => $this->t("Internal tracker Method"),
      '#description' => $this->t("Client side tracker can get more details but work for the web browser with JS enabled only."),
      '#options' => [0 => 'Client side (Java Script)', 1 => 'Server side'],
      '#default_value' => $config->get('sa_tracker_server'),
    ];
    $form['sa_track']['sa_tracker_duration'] = [
      '#type' => 'select',
      '#title' => $this->t("Keep reports for :"),
      '#options' => $durations,
      '#default_value' => $config->get('sa_tracker_duration'),
    ];

    // Tracking configuration.
    $form['tracking'] = [
      '#type' => 'details',
      '#title' => $this->t("Tracking Configuration"),
      '#open' => TRUE,
    ];
    $form['tracking']['track_admin'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Track Admin Pages"),
      '#default_value' => $config->get('track_admin'),
    ];
    $form['tracking']['track_auth'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Track Authenticated Users"),
      '#default_value' => $config->get('track_auth'),
    ];
    $form['tracking']['track_exclude_url'] = [
      '#type' => 'textarea',
      '#title' => $this->t("Exclude urls list"),
      '#default_value' => implode("\n", $config->get('track_exclude_url')),
      '#description' => $this->t("One url per line. All urls are widecast. (/admin/config = */admin/config*"),
    ];

    // General configuration.
    $form['config'] = [
      '#type' => 'details',
      '#title' => $this->t("General Configuration"),
      '#open' => TRUE,
    ];
    $form['config']['displaystat'] = [
      '#type' => 'select',
      '#title' => $this->t('Number of days to display'),
      '#options' => $durations,
      '#default_value' => $config->get('displaystat'),
    ];

    // Check chartist-js lib.
    $lib_chartist_exist = SimpleAnalyticsHelper::checkLibraries(FALSE);
    $form['config']['lib_chartist'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Use Chartist-JS graph"),
      '#description' => $this->t("Please refer to the Simple Analytics help page for the details."),
      '#default_value' => $config->get('lib_chartist'),
    ];
    // Conditional activations.
    if (!$lib_chartist_exist) {
      $form['config']['lib_chartist']['#attributes']['disabled'] = 'disabled';
      $desc = $this->t("To activate, Please add the Chartist-JS library. Please refer to the Simple Analytics help page for the details.");
      $form['config']['lib_chartist']['#description'] = $desc;
      $form['config']['lib_chartist']['#default_value'] = FALSE;
    }

    $form['config']['live_period'] = [
      '#type' => 'number',
      '#title' => $this->t("Live view update period"),
      '#description' => $this->t("Refresh every X seconds"),
      '#default_value' => $config->get('live_period'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    if ($form_state->getValue('piwik-uri') && $form_state->getValue('piwik-id') === "") {
      $form_state->setErrorByName('piwik-id', $this->t('If you set a piwik URL, You must also specify the Piwik ID.'));
    }
    elseif (!$form_state->getValue('piwik-uri') && $form_state->getValue('piwik-id') !== "") {
      $form['piwik']['piwik-id']['#value'] = '';
      $form_state->setErrorByName('piwik-uri', $this->t('If you set a piwik ID, You must also specify the Piwik URL (Id removed, you can save now).'));
    }
    if ($form_state->getValue('piwik-uri') && filter_var($form_state->getValue('piwik-uri'), FILTER_VALIDATE_URL) === FALSE) {
      $form_state->setErrorByName('piwik-uri', $this->t('Piwik URL is incorrect.'));
    }
    if (!($form_state->getValue('live_period') > 0)) {
      $form_state->setErrorByName('live_period', $this->t('Live update period must > 0'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $piwik_url = $form_state->getValue('piwik-uri');
    $piwik_url = str_replace(["http:", "https:"], "", $piwik_url);
    if (substr($piwik_url, -1) === '/') {
      $piwik_url = substr($piwik_url, 0, -1);
    }

    $config = SimpleAnalyticsHelper::getConfig(TRUE);
    $config->set('piwik-uri', $piwik_url);

    $fields = [
      'google-id',
      'piwik-id',
      'custom',
      'custom-noscript',
      'track_admin',
      'track_auth',
      'displaystat',
      'lib_chartist',
      'live_period',
      'sa_tracker',
      'sa_tracker_server',
      'sa_tracker_duration',
    ];
    foreach ($fields as $field) {
      $config->set($field, $form_state->getValue($field));
    }

    // Save excluded urls as array.
    $urls = explode("\n", $form_state->getValue('track_exclude_url'));
    foreach ($urls as $key => $url) {
      $urls[$key] = trim($url);
    }
    $config->set('track_exclude_url', $urls);

    // Save config.
    $config->save();

    return parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      SimpleAnalyticsHelper::getConfigName(),
    ];
  }

}
