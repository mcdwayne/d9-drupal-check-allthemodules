<?php

namespace Drupal\google_analytics_light_report\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;

/**
 * Class GoogleAnalyticsLightReportSettingsForm.
 */
class GoogleAnalyticsLightReportSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {

    return 'google_analytics_light_report_settings_form';

  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'google_analytics_light_report_settings_form.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_analytics_light_report_settings_form.settings');

    $form['guide_text'] = [
      '#type' => 'markup',
      '#markup' => '<h2 id="enable">Step 1: Enable the Analytics API</h2>
	  <p>To get started using Google Analytics API, you need to first <a href="https://console.developers.google.com/start/api?id=analytics&credential=client_key">use  the setup tool</a>, which guides you through creating a project in the  Google API Console, enabling the API, and creating credentials.</p>
	  <h3 id="clientId">Create a client ID</h3>
	  <ol>
	    <li>Open the <a href="https://console.developers.google.com/iam-admin/serviceaccounts"><strong>Service accounts</strong> page</a>. If prompted,  select a project.</li>
	    <li>Click <strong>Create service account</strong>.</li>
	    <li> In the <strong>Create service account</strong> window, type a name for the service      account, and select <strong>Furnish a new private key</strong>. If you want to <a href="https://developers.google.com/identity/protocols/OAuth2ServiceAccount#delegatingauthority">grant      G Suite domain-wide authority</a> to the service account, also select <strong>Enable G Suite Domain-wide Delegation</strong>.            Then click <strong>Save</strong>.</li>
	  </ol>
	  <p>Your new public/private key pair is generated and downloaded to your machine;  it serves as the only copy of this key. You are responsible for storing it  securely.</p>
	  <h3 id="add-user">Step 2: Add service account to Google Analytics account</h3>
	  <p>The newly created service account will have an email address,    &lt;projectId&gt;-&lt;uniqueId&gt;@developer.gserviceaccount.com;    Use this email address to <a href="https://support.google.com/analytics/answer/1009702">add    a user</a> to the Google analytics account you want to access via the API.    For this tutorial only <a href="https://support.google.com/analytics/answer/2884495">Read    &amp; Analyze</a> permissions are needed. </p>
	  <p> For more detail <a href="https://developers.google.com/analytics/devguides/reporting/core/v3/quickstart/service-php">click here</a></p>
	  <p><strong>Step 3: Upload the previously downloaded service-account-credentials.json below.</strong></p>
	  <p>&nbsp;</p>',
    ];
    $form['google_analytics_light_json_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Service account credentials'),
      '#default_value' => $config->get('google_analytics_light_json_file'),
      '#upload_location' => 'public://google_analytics_light_reports',
      '#description' => $this->t('Upload a file, allowed extensions: json.Use the developers console and download your service account credentials in JSON format.'),
      '#upload_validators' => [
        'file_validate_extensions' => ['json'],
      ],
    ];
    $fid = $config->get('google_analytics_light_json_file');
    $key_file_location = '';
    if (!empty($fid)) {
      $file_obj = File::load($fid[0]);
      if (gettype($file_obj) == 'object') {
        $key_file_location = drupal_realpath($file_obj->getFileuri());
      }
    }
    if (!empty($key_file_location)) {
      $options = [];
      $library_exist = google_analytics_light_report_library_exists();
      if ($library_exist) {
        $analytics = google_analytics_light_report_initialize_analytics();
        $accounts = $analytics->management_accounts->listManagementAccounts();
        if (count($accounts->getItems()) > 0) {
          foreach ($accounts->getItems() as $account) {
            $options[$account->getId()] = $account->getName();
          }
        }
        $form['g_analytics_report_account'] = [
          '#type' => 'select',
          '#title' => $this->t('Select Account'),
          "#empty_option" => $this->t('- Select -'),
          '#options' => $options,
          '#default_value' => !empty($form_state->getValue('g_analytics_report_account')) ? $form_state->getValue('g_analytics_report_account') : $config->get('g_analytics_report_account', ''),
          '#ajax' => [
            'wrapper' => 'matching-property-wrapper',
            'callback' => '::google_analytics_light_report_account_list_ajax_callback',
          ],
        ];
        $form['g_analytics_report_account_wrapper'] = [
          '#type' => 'container',
          '#attributes' => ['id' => 'matching-property-wrapper'],
        ];
        $form['g_analytics_report_view_wrapper'] = [
          '#type' => 'container',
          '#attributes' => ['id' => 'matching-view-wrapper'],
        ];
        $selected_property = [];
        $account_id = !empty($form_state->getValue('g_analytics_report_account')) ? $form_state->getValue('g_analytics_report_account') : $config->get('g_analytics_report_account', '');
        if (!empty($account_id)) {
          // Get the list of properties for the authorized user.
          $properties = $analytics->management_webproperties->listManagementWebproperties($account_id);
          if (count($properties->getItems()) > 0) {
            foreach ($properties->getItems() as $pr_items) {
              $selected_property[$pr_items->getId()] = $pr_items->getName();
            }
          }
          $form['g_analytics_report_account_wrapper']['g_analytics_report_property'] = [
            '#type' => 'select',
            '#empty_option' => $this->t('- Select -'),
            '#title' => $this->t('Select Property'),
            '#options' => $selected_property,
            '#default_value' => !empty($form_state->getValue('g_analytics_report_property')) ? $form_state->getValue('g_analytics_report_property') : $config->get('g_analytics_report_property', ''),
            '#ajax' => [
              'wrapper' => 'matching-view-wrapper',
              'callback' => '::google_analytics_light_report_view_list_ajax_callback',
            ],
          ];

          $property_id = !empty($form_state->getValue('g_analytics_report_property')) ? $form_state->getValue('g_analytics_report_property') : $config->get('g_analytics_report_property', '');
          if (!empty($property_id)) {
            $selected_view = [];
            // Get the list of views (profiles) for the authorized user.
            $profiles = $analytics->management_profiles->listManagementProfiles($account_id, $property_id);
            if (count($profiles->getItems()) > 0) {
              foreach ($profiles->getItems() as $profile) {
                $selected_view[$profile->getId()] = $profile->getName();
              }
            }
            $form['g_analytics_report_view_wrapper']['g_analytics_report_view'] = [
              '#type' => 'select',
              '#empty_option' => $this->t('- Select -'),
              '#title' => $this->t('Select view'),
              '#options' => $selected_view,
              '#default_value' => !empty($form_state->getValue('g_analytics_report_view')) ? $form_state->getValue('g_analytics_report_view') : $config->get('g_analytics_report_view', ''),
            ];
          }
        }
      }
    }

    // Add a submit button that handles the submission of the form.
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Submit'),
      ],
    ];
    return $form;

  }

  /**
   * Ajax callback for the account dropdown.
   */
  public function google_analytics_light_report_account_list_ajax_callback(array $form, FormStateInterface $form_state) {
    return $form['g_analytics_report_account_wrapper'];
  }

  /**
   * Ajax callback for the account dropdown.
   */
  public function google_analytics_light_report_view_list_ajax_callback(array $form, FormStateInterface $form_state) {
    return $form['g_analytics_report_view_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $get_json_file = $form_state->getValue('google_analytics_light_json_file');
    $bgfile = File::load($get_json_file[0]);
    $file_usage = \Drupal::service('file.usage');
    if (gettype($bgfile) == 'object') {
      $bgfile->setPermanent();
      $bgfile->save();
      $file_usage->add($bgfile, 'google_analytics_light_report', 'file', $get_json_file[0]);
    }
    $this->configFactory->getEditable('google_analytics_light_report_settings_form.settings')
      ->set('google_analytics_light_json_file', $form_state->getValue('google_analytics_light_json_file'))
      ->set('g_analytics_report_account', $form_state->getValue('g_analytics_report_account'))
      ->set('g_analytics_report_property', $form_state->getValue('g_analytics_report_property'))
      ->set('g_analytics_report_view', $form_state->getValue('g_analytics_report_view'))
      ->save();
    parent::submitForm($form, $form_state);
  }

}
