<?php

namespace Drupal\feeds_twitter\Feeds\Fetcher\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\feeds\Plugin\Type\ExternalPluginFormBase;

/**
 * The configuration form for http fetchers.
 */
class FeedsTwitterFetcherForm extends ExternalPluginFormBase {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API key'),
      '#default_value' => $this->plugin->getConfiguration('api_key'),
      '#required' => TRUE
    ];
    $form['api_secret_key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API secret key'),
      '#default_value' => $this->plugin->getConfiguration('api_secret_key'),
      '#required' => TRUE
    ];
    $form['access_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access token'),
      '#default_value' => $this->plugin->getConfiguration('access_token'),
      '#required' => TRUE
    ];
    $form['access_token_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Access token secret'),
      '#default_value' => $this->plugin->getConfiguration('access_token_secret'),
      '#required' => TRUE
    ];
    $form['fetch_quantity'] = [
      '#type' => 'number',
      '#title' => $this->t('Tweets to fetch per import'),
      '#default_value' => $this->plugin->getConfiguration('fetch_quantity'),
      '#required' => TRUE
    ];
    $form['include_retweets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Include retweets'),
      '#default_value' => $this->plugin->getConfiguration('include_retweets')
    ];
    $form['use_simplified_json'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use simplified JSON response'),
      '#default_value' => $this->plugin->getConfiguration('include_retweets')
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state, FeedInterface $feed = NULL) {
    // Twitter makes it easy to pick up extra spaces, trim all values before saving
    $values = $form_state->getValues();
    foreach ($values as &$value) {
      $value = trim($value);
    }
    $this->plugin->setConfiguration($values);
  }

}
