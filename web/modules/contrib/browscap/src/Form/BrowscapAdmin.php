<?php

namespace Drupal\browscap\Form;

use Drupal\browscap\BrowscapEndpoint;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;

/**
 * Defines Browscap administration form.
 */
class BrowscapAdmin extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'browscap_admin';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'browscap.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = \Drupal::config('browscap.settings');
    $form = [];

    // Check the local browscap data version number.
    $version = $config->get('version');

    // If the version number is 0 then browscap data has never been fetched.
    if ($version == 0) {
      $version = $this->t('Never fetched');
    }

    $form['data'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('User agent detection settings'),
    ];
    $form['data']['browscap_data_version'] = [
      '#markup' => '<p>' . $this->t('Current browscap data version: %fileversion.', ['%fileversion' => $version]) . '</p>',
    ];
    $form['data']['browscap_enable_automatic_updates'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable automatic updates'),
      '#default_value' => $config->get('enable_automatic_updates'),
      '#description' => $this->t('Automatically update the user agent detection information.'),
    ];
    $options = [
      3600, 10800, 21600, 32400, 43200, 86400, 172800, 259200, 604800, 1209600,
      2419200, 4838400, 9676800,
    ];
    $dateformatter = \Drupal::service('date.formatter');
    $form['data']['browscap_automatic_updates_timer'] = [
      '#type' => 'select',
      '#title' => $this->t('Check for new user agent detection information every'),
      '#default_value' => $config->get('automatic_updates_timer'),
      '#options' => array_map([$dateformatter, 'formatInterval'], array_combine($options, $options)),
      '#description' => $this->t('Newer user agent detection information will be automatically downloaded and installed. (Requires a correctly configured @cron_link).', ['@cron_link' => \Drupal::l("cron maintenance task", Url::fromRoute('system.status'))]),
      '#states' => [
        'visible' => [
          ':input[name="browscap_enable_automatic_updates"]' => ['checked' => TRUE],
        ],
      ],
    ];

    $form['data']['browscap_version_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Browscap version URL'),
      '#default_value' => $config->get('version_url'),
      '#description' => $this->t('The URL to the information about the current Browscap version available.'),
    ];
    $form['data']['browscap_data_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Browscap data URL'),
      '#default_value' => $config->get('data_url'),
      '#description' => $this->t('The URL to Browscap data.'),
    ];

    $form['actions']['browscap_refresh'] = [
      '#type' => 'submit',
      '#value' => $this->t('Refresh browscap data'),
      '#submit' => ['::refreshSubmit'],
      '#weight' => 10,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('browscap.settings')
      ->set('automatic_updates_timer', $form_state->getValue('browscap_automatic_updates_timer'))
      ->set('enable_automatic_updates', $form_state->getValue('browscap_enable_automatic_updates'))
      ->set('data_url', $form_state->getValue('browscap_data_url'))
      ->set('version_url', $form_state->getValue('browscap_version_url'))
      ->save();
    parent::submitForm($form, $form_state);
  }

  /**
   * Submit callback for the 'Refresh browscap data' submit.
   *
   * Performs an import then records when it completed.
   */
  public function refreshSubmit(array &$form, FormStateInterface $form_state) {
    // Update the browscap information.
    $endpoint = new BrowscapEndpoint();
    \Drupal::service('browscap.importer')->import($endpoint, FALSE);

    // Record when the browscap information was updated.
    $this->config('browscap.settings')
      ->set('imported', REQUEST_TIME)
      ->save();
  }

}
