<?php

/**
 * @file
 * Contains \Drupal\social_counters\Plugin\SocialCounters\Pinterest.
 */

namespace Drupal\social_counters\Plugin\SocialCounters;

use Drupal\Core\Url;
use Drupal\social_counters\Plugin\SocialCountersPluginBase;

/**
 * Provides a Pinterest social counters plugin.
 * @Plugin(
 *   id = "social_counters_pinterest",
 *   label = @Translation("Pinterest"),
 * )
 */
class Pinterest extends SocialCountersPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getCount() {
    $count = 0;
    $config = $this->configuration['config'];

    $url = Url::fromUri('https://api.pinterest.com/v1/users/' . $config->id, array('query' => array(
      'access_token' => $config->token,
      'fields' => 'counts',
    )))->toString();

    try {
      $response = $this->http_client->request('GET', $url);
      $result = $this->json_serializer->decode((string)$response->getBody());
      if (!empty($result['data'])) {
        $count = $result['data']['counts']['followers'];
      }
      else {
        $this->logger->warning('%message', array('%message' => $result['message']));
        $message = 'Pinterest: ' . $result['message'];
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
    return $this->t('Pinterest');
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(&$form, &$form_state, $config) {
    $form['id'] = array(
      '#title' => $this->t('Pinterest username'),
      '#description' => $this->t("Pinterest username or id. Examples: 'cocacola' or '205265832924678423'"),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => !empty($config->id) ? $config->id : '',
    );

    $form['token'] = array(
      '#title' => $this->t('Access Token'),
      '#description' => t('You can read how to generate Pinterest Access Token at  <a href="@dev-pinterest">developers.pinterest.com</a>.', array(
          '@dev-pinterest' => Url::fromUri('https://developers.pinterest.com/docs/api/authentication/')->toString()
        )),
      '#type' => 'textfield',
      '#maxlength' => 256,
      '#required' => TRUE,
      '#default_value' => !empty($config->token) ? $config->token : '',
    );
  }
}
