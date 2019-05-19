<?php

/**
 * @file
 * Contains \Drupal\social_counters\Plugin\SocialCounters\GooglePlus.
 */
namespace Drupal\social_counters\Plugin\SocialCounters;

use Drupal\Core\Url;
use Drupal\social_counters\Plugin\SocialCountersPluginBase;

/**
 * Provides a Google Plus social counters plugin.
 * @Plugin(
 *   id = "social_counters_google_plus",
 *   label = @Translation("Google"),
 * )
 */
class GooglePlus extends SocialCountersPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getCount() {
    $count = 0;
    $config = $this->configuration['config'];

    $url = Url::fromUri('https://www.googleapis.com/plus/v1/people/' . $config->id, array('query' => array(
      'key' => $config->key,
    )))->toString();

    try {
      $response = $this->http_client->request('GET', $url);
      $result = $this->json_serializer->decode((string)$response->getBody());
      if ($response->getStatusCode() == 200) {
        $count = $result['circledByCount'];
      }
      else {
        $this->logger->warning('%message', array('%message' => $result['message']));
      }
    }
    catch (RequestException $e) {
      // @todo Find out if we can do it without global function.
      watchdog_exception('social_counters', $e);
    }

    return $count;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->t('Google Plus');
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(&$form, &$form_state, $config) {
    $form['id'] = array(
      '#title' => $this->t('Google Plus Name (or User ID)'),
      '#description' => $this->t("Either the page name or ID. Examples: '+Coca-Cola' or '113050383214450284645'"),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => !empty($config->id) ? $config->id : '',
    );

    $form['key'] = array(
      '#title' => $this->t('Google Plus API Key'),
      '#description' => t('You can generate Google Plus API Key at <a href="@dev-google">developers.google.com</a>.', array(
          '@dev-google' => Url::fromUri('https://developers.google.com/api-client-library/python/guide/aaa_apikeys')->toString()
        )),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => !empty($config->key) ? $config->key : '',
    );
  }
}
