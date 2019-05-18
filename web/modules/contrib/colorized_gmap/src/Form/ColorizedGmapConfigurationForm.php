<?php

namespace Drupal\colorized_gmap\Form;

use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Defines a form that configures forms module settings.
 */
class ColorizedGmapConfigurationForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'colorized_gmap_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'colorized_gmap.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, Request $request = NULL) {
    $config = $this->config('colorized_gmap.settings');

    $api_key_url = Url::fromUri('https://developers.google.com/maps/documentation/javascript/get-api-key', [
      'attributes' => ['target' => '_blank'],
      'absolute' => TRUE,
    ]);
    $api_key_link = Link::fromTextAndUrl($this->t('this'), $api_key_url)->toString();
    $client_id_url = Url::fromUri('https://developers.google.com/maps/documentation/javascript/get-api-key#client-id', [
      'attributes' => ['target' => '_blank'],
      'absolute' => TRUE,
    ]);
    $client_id_link = Link::fromTextAndUrl($this->t('this'), $client_id_url)->toString();
    $private_key_url = Url::fromUri('https://developers.google.com/maps/documentation/business/webservices/auth#how_do_i_get_my_signing_key', [
      'attributes' => ['target' => '_blank'],
      'absolute' => TRUE,
    ]);
    $private_key_link = Link::fromTextAndUrl($this->t('this'), $private_key_url)->toString();

    $form['colorized_gmap_auth_method'] = [
      '#type' => 'select',
      '#title' => $this->t('Google API Authentication Method'),
      '#description' => $this->t('Google API Authentication Method'),
      '#default_value' => $config->get('colorized_gmap_auth_method', 1),
      '#options' => [
        1 => $this->t('API Key'),
        2 => $this->t('Google Maps API for Work'),
      ],
    ];
    $form['colorized_gmap_api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Places API Key'),
      '#default_value' => $config->get('colorized_gmap_api_key'),
      '#description' => $this->t('Please visit @get-key page to get API key. This module will not work without it.', ['@get-key' => $api_key_link]),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="colorized_gmap_auth_method"]' => ['value' => 1],
        ],
      ],
    ];
    $form['colorized_gmap_client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API for Work: Client ID'),
      '#description' => $this->t('For more information, visit @client-id page', ['@client-id' => $client_id_link]),
      '#default_value' => $config->get('colorized_gmap_client_id'),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="colorized_gmap_auth_method"]' => ['value' => 2],
        ],
      ],
    ];

    $form['colorized_gmap_private_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Google Maps API for Work: Private/Signing Key'),
      '#description' => $this->t('For more information, visit @private-key page', ['@private-key' => $private_key_link]),
      '#default_value' => $config->get('colorized_gmap_private_key'),
      '#required' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="colorized_gmap_auth_method"]' => ['value' => 2],
        ],
      ],
    ];
    $form['#attached']['library'][] = 'colorized_gmap/colorized_gmap.colorpicker';

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    $auth_method = $form_state->getValue('colorized_gmap_auth_method');

    if ($auth_method == 1 && empty($form_state->getValue('colorized_gmap_api_key'))) {
      $form_state->setErrorByName('colorized_gmap_api_key', $this->t('Google Places API Key is required.'));
    }
    if ($auth_method == 2 && empty($form_state->getValue('colorized_gmap_client_id'))) {
      $form_state->setErrorByName('colorized_gmap_client_id', $this->t('Client ID is required.'));
    }
    if ($auth_method == 2 && empty($form_state->getValue('colorized_gmap_private_key'))) {
      $form_state->setErrorByName('colorized_gmap_private_key', $this->t('Private/Signing Key is required.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $this->config('colorized_gmap.settings')
      ->set('colorized_gmap_auth_method', $values['colorized_gmap_auth_method'])
      ->save();
    $this->config('colorized_gmap.settings')
      ->set('colorized_gmap_api_key', $values['colorized_gmap_api_key'])
      ->save();
    $this->config('colorized_gmap.settings')
      ->set('colorized_gmap_client_id', $values['colorized_gmap_client_id'])
      ->save();
    $this->config('colorized_gmap.settings')
      ->set('colorized_gmap_private_key', $values['colorized_gmap_private_key'])
      ->save();

    $this->messenger()->addMessage('Settings are saved');
  }

}
