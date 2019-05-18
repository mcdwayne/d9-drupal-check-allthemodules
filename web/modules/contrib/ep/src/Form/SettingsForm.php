<?php

namespace Drupal\ep\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Elastic path settings form.
 */
class SettingsForm extends ConfigFormBase {

  const AUTH_URI = '/cortex/oauth2/tokens';
  const CART_URI = '/cortex/carts/!cortex_store/default';
  const PRODUCT_URI = '/cortex/items';
  const NAVIGATION_URI = '/cortex/navigation';
  const SEARCH_URI = '/cortex/search';
  const LOOKUP_URI = '/cortex/lookups';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'ep_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'ep.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('ep.settings');

    $form['cortex'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Cortex API Settings'),
      '#tree' => TRUE,
    ];

    $form['cortex']['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Elastic Path Base url'),
      '#description' => $this->t('The base url of your Elastic path endpoint.'),
      '#default_value' => $config->get('cortex.base_url'),
      '#placeholder' => 'https://example.com',
      '#required' => TRUE,
    ];

    $form['cortex']['store'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Store'),
      '#description' => $this->t('The Active Store ID of your Elastic Path.'),
      '#default_value' => $config->get('cortex.store'),
      '#placeholder' => 'online',
      '#required' => TRUE,
    ];

    $form['cortex']['oauth2_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('OAuth2 Endpoint'),
      '#description' => $this->t('Cortex API Authorization Endpoint URI. [Prefix "/"].'),
      '#default_value' => $config->get('cortex.oauth2_uri') ? $config->get('cortex.oauth2_uri') : self::AUTH_URI,
    ];

    $form['cortex']['cart_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Cart Endpoint'),
      '#description' => $this->t('Cortex API endpoint to manage Cart. [Prefix "/"]'),
      '#default_value' => $config->get('cortex.cart_uri') ? $config->get('cortex.cart_uri') : self::CART_URI,
    ];

    $form['cortex']['product_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Product Endpoint'),
      '#description' => $this->t('Cortex API endpoint to get Products. [Prefix "/"]'),
      '#default_value' => $config->get('cortex.product_uri') ? $config->get('cortex.product_uri') : self::PRODUCT_URI,
    ];

    $form['cortex']['navigation_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Navigation Endpoint'),
      '#description' => $this->t('Cortex API endpoint to get collections/categories. [Prefix "/"]'),
      '#default_value' => $config->get('cortex.navigation_uri') ? $config->get('cortex.navigation_uri') : self::NAVIGATION_URI,
    ];

    $form['cortex']['search_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Search Endpoint'),
      '#description' => $this->t('Cortex API endpoint to get collections/categories. [Prefix "/"]'),
      '#default_value' => $config->get('cortex.cortex_search_uri') ? $config->get('cortex.csearch_uri') : self::SEARCH_URI,
    ];
    $form['cortex']['lookups_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Lookups Endpoint'),
      '#description' => $this->t('Cortex API endpoint to get collections/categories. [Prefix "/"]'),
      '#default_value' => $config->get('cortex.search_uri') ? $config->get('cortex.lookups_uri') : self::LOOKUP_URI,
    ];
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory()->getEditable('ep.settings')
      ->set('cortex.base_url', $form_state->getValue(['cortex', 'base_url']))
      ->set('cortex.store', $form_state->getValue(['cortex', 'store']))
      ->set('cortex.oauth2_uri', $form_state->getValue(['cortex', 'oauth2_uri']))
      ->set('cortex.cart_uri', $form_state->getValue(['cortex', 'cart_uri']))
      ->set('cortex.product_uri', $form_state->getValue(['cortex', 'product_uri']))
      ->set('cortex.navigation_uri', $form_state->getValue(['cortex', 'navigation_uri']))
      ->set('cortex.search_uri', $form_state->getValue(['cortex', 'search_uri']))
      ->set('cortex.lookups_uri', $form_state->getValue(['cortex', 'lookups_uri']))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
