<?php

/**
 * @file
 * Contains \Drupal\social_counters\Plugin\SocialCounters\YouTube.
 */

namespace Drupal\social_counters\Plugin\SocialCounters;

use Drupal\Core\Url;
use Drupal\social_counters\Plugin\SocialCountersPluginBase;

/**
 * Provides a YouTube social counters plugin.
 * @Plugin(
 *   id = "social_counters_youtube",
 *   label = @Translation("YouTube"),
 * )
 */
class YouTube extends SocialCountersPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getCount() {
    $count = 0;
    $config = $this->configuration['config'];

    $url = Url::fromUri('https://www.googleapis.com/youtube/v3/channels', array('query' => array(
      'forUsername' => $config->id,
      'key' => $config->key,
      'part' => 'statistics',
    )))->toString();

    try {
      $response = $this->http_client->request('GET', $url);
      $result = $this->json_serializer->decode((string)$response->getBody());
      if (!isset($result['error'])) {
        $count = $result['items'][0]['statistics']['subscriberCount'];
      }
      else {
        foreach ($result['error']['errors'] as $error) {
          $this->logger->warning('%message', array('%message' => $error['message']));
        }
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
    return $this->t('YouTube');
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(&$form, &$form_state, $config) {
    $form['id'] = array(
      '#title' => $this->t('YouTube username'),
      '#description' => $this->t('YouTube username. Examples: cocacola'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => !empty($config->id) ? $config->id : '',
    );

    $form['key'] = array(
      '#title' => $this->t('Google Plus API Key'),
      '#description' => $this->t('You can generate Google Plus API Key at <a href="@dev-google">developers.google.com</a>.', array(
        '@dev-google' => Url::fromUri('https://developers.google.com/api-client-library/python/guide/aaa_apikeys')->toString()
      )),
      '#type' => 'textfield',
      '#maxlength' => 256,
      '#required' => TRUE,
      '#default_value' => !empty($config->key) ? $config->key : '',
    );
  }
}
