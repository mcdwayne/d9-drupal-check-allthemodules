<?php

namespace Drupal\ga_reports\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\ga_reports\GaReportsApiFeed;
use Drupal\Component\Serialization\Json;

/**
 * Represents the admin settings form for ga_reports_api.
 */
class GaReportsApiAdminSettingsForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ga_reports_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['ga_reports_api.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $account = ga_reports_gafeed();
    $config = $this->config('ga_reports_api.settings');
    global $base_url;

    // There are no profiles, and we should just leave it at setup.
    if (!$account) {
      $form['setup'] = [
        '#type' => 'details',
        '#title' => $this->t('Setup Google Analytics Reports'),
        '#open' => TRUE,
      ];
      $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
      $form['setup']['client_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Client ID'),
        '#default_value' => $config->get('client_id'),
        '#size' => 75,
        '#description' => $this->t('Client ID generated for your project by Google Developers Console.'),
        '#required' => TRUE,
      ];

      $form['setup']['client_secret'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Client Secret Key'),
        '#default_value' => $config->get('client_secret'),
        '#size' => 75,
        '#description' => $this->t('Client Secret generated for your porject by Google Developers Console'),
        '#required' => TRUE,
      ];
      $form['setup']['google_analytics_account'] = [
        '#default_value' => $config->get('account'),
        '#description' => $this->t('This ID is unique to each site you want to 
        track separately, and is in the form of UA-xxxxxxx-yy. To get a Web 
        Property ID, <a href=":analytics">register your site with Google 
        Analytics</a>, or if you already have registered your site, go to your 
        Google Analytics Settings page to see the ID next to every site profile.
        <a href=":webpropertyid">Find more information in the documentation
        </a>.', [
          ':analytics' => 'http://www.google.com/analytics/',
          ':webpropertyid' =>
          Url::fromUri('https://developers.google.com/analytics/resources/concepts/gaConceptsAccounts',
              ['fragment' => 'webProperty'])->toString(),
        ]),
        '#maxlength' => 20,
        '#placeholder' => 'UA-',
        '#size' => 75,
        '#title' => $this->t('Web Property ID'),
        '#type' => 'textfield',
      ];
      $form['setup']['setup_submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Setup Account'),
        '#submit' => ['::adminSubmitSetup'],
      ];
      $detail_url = "$base_url/admin/ga-report/help";
      $form['setup']['detail'] = [
        '#type' => 'link',
        '#title' => $this->t('HELP'),
        '#url' => Url::fromUri($detail_url),
        '#attributes' => [
          'title' => 'Details',
          'class' => ['use-ajax', 'views-action-details'],
          'data-dialog-type' => 'modal',
          'data-dialog-options' => Json::encode([
            'title' => 'SETUP GOOGLE ANALYTICS REPORTS HELP',
            'width' => 'auto',
            'height' => 'auto',
          ]),
        ],
      ];
    }
    elseif ($account->isAuthenticated()) {
      // Load profiles list.
      $profile_list = ga_reports_profiles_list();

      $form['settings'] = [
        '#type' => 'details',
        '#title' => $this->t('Settings'),
        '#open' => TRUE,
      ];

      $profile_info = '';
      if (isset($profile_list['current_profile'])) {
        $profile_info = parse_url($profile_list['current_profile']->websiteUrl, PHP_URL_HOST) . ' - ' . $profile_list['current_profile']->name . ' (' . $profile_list['current_profile']->id . ')';
      }

      $form['settings']['profile_id'] = [
        '#type' => 'select',
        '#title' => $this->t('Reports profile'),
        '#options' => $profile_list['options'],
        '#default_value' => $profile_list['profile_id'],
        '#description' => $this->t('Choose your Google Analytics profile. The currently active profile is: %profile.', ['%profile' => $profile_info]),
      ];

      // Default cache periods.
      $times = [];
      // 1-6 days.
      for ($days = 1; $days <= 6; $days++) {
        $times[] = $days * 60 * 60 * 24;
      }
      // 1-4 weeks.
      for ($weeks = 1; $weeks <= 4; $weeks++) {
        $times[] = $weeks * 60 * 60 * 24 * 7;
      }

      $date_formatter = \Drupal::service('date.formatter');
      $options = array_map([
        $date_formatter,
        'formatInterval',
      ], array_combine($times, $times));

      $form['settings']['cache_length'] = [
        '#type' => 'select',
        '#title' => $this->t('Query cache'),
        '#description' => $this->t('The <a href="@link">Google Analytics Quota Policy</a> restricts the number of queries hit per day. This limits the creation of new reports on your site. We recommend to cache your settings.', [
          '@link' => Url::fromUri('https://developers.google.com/analytics/devguides/reporting/core/v3/limits-quotas', [
            'fragment' => 'core_reporting',
          ])->toString(),
        ]),
        '#options' => $options,
        '#default_value' => $config->get('cache_length'),
      ];

      $form['settings']['settings_submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#submit' => ['::adminSubmitSettings'],
      ];
      $form['revoke'] = [
        '#type' => 'details',
        '#title' => $this->t('Revoke access and Signout'),
        '#description' => $this->t('Revoke your access token from Google Analytics. This action will logout you and will stop accessing your reports.'),
      ];
      $form['revoke']['revoke_submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Revoke access token'),
        '#submit' => ['::adminSubmitRevoke'],
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
    if (!empty($form_state->getValue('google_analytics_account'))) {
      if (!preg_match('/^UA-\d+-\d+$/', $form_state->getValue('google_analytics_account'))) {
        $form_state->setErrorByName('google_analytics_account', $this->t('A valid Google Analytics Web Property ID is case sensitive and formatted like UA-xxxxxxx-yy.'));
      }
    }

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * Save Google Analytics Reports API admin setup.
   */
  public function adminSubmitSetup(array &$form, FormStateInterface $form_state) {
    $redirect_uri = GaReportsApiFeed::currentUrl();

    $config = \Drupal::configFactory()->getEditable('ga_reports_api.settings');
    $config
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('account', $form_state->getValue('google_analytics_account'))
      ->set('redirect_uri', $redirect_uri)
      ->save();

    $ga_reports_feed = new GaReportsApiFeed();
    $ga_reports_feed->beginAuthentication($form_state->getValue('client_id'), $redirect_uri);
  }

  /**
   * Save Google Analytics Reports API settings.
   */
  public function adminSubmitSettings(array &$form, FormStateInterface $form_state) {
    $config = \Drupal::configFactory()->getEditable('ga_reports_api.settings');
    $config
      ->set('profile_id', $form_state->getValue('profile_id'))
      ->set('cache_length', $form_state->getValue('cache_length'))
      ->save();
    drupal_set_message($this->t('Settings have been saved successfully.'));
  }

  /**
   * Revoke Google Analytics access token.
   */
  public function adminSubmitRevoke(array &$form, FormStateInterface $form_state) {
    ga_reports_revoke();
    drupal_set_message($this->t('Access token has been successfully revoked.'));
  }

}
