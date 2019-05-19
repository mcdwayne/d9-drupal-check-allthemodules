<?php

namespace Drupal\styled_google_map\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\styled_google_map\StyledGoogleMapInterface;

/**
 * Configure example settings for this site.
 */
class StyledGoogleMapSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'styled_google_map_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'styled_google_map.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('styled_google_map.settings');

    $form['styled_google_map_google_auth_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Google API Authentication Method'),
      '#options' => [
        StyledGoogleMapInterface::STYLED_GOOGLE_MAP_GOOGLE_AUTH_KEY => t('API Key'),
        StyledGoogleMapInterface::STYLED_GOOGLE_MAP_GOOGLE_AUTH_WORK => t('Google Maps API for Work'),
      ],
      '#default_value' => $config->get('styled_google_map_google_auth_method'),
    ];

    $form['styled_google_map_google_apikey'] = [
      '#type' => 'textfield',
      '#title' => t('Google Maps API Key'),
      '#description' => $this->t('Obtain a Google Maps Javascript API key at <a href="@link">@link</a>', [
        '@link' => 'https://developers.google.com/maps/documentation/javascript/get-api-key',
      ]),
      '#default_value' => $config->get('styled_google_map_google_apikey'),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="styled_google_map_google_auth_method"]' => ['value' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_GOOGLE_AUTH_KEY],
        ],
      ],
    ];
    $form['styled_google_map_google_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API for Work: Client ID'),
      '#description' => $this->t('For more information, visit: <a href="@link">@link</a>', [
        '@link' => 'https://developers.google.com/maps/documentation/javascript/get-api-key#client-id',
      ]),
      '#default_value' => $config->get('styled_google_map_google_client_id'),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="styled_google_map_google_auth_method"]' => ['value' => StyledGoogleMapInterface::STYLED_GOOGLE_MAP_GOOGLE_AUTH_WORK],
        ],
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\Core\Config\Config $config */
    $config = \Drupal::service('config.factory')
      ->getEditable('styled_google_map.settings');
    $config->set('styled_google_map_google_auth_method', $form_state->getValue('styled_google_map_google_auth_method'))
      ->set('styled_google_map_google_apikey', $form_state->getValue('styled_google_map_google_apikey'))
      ->set('styled_google_map_google_client_id', $form_state->getValue('styled_google_map_google_client_id'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
