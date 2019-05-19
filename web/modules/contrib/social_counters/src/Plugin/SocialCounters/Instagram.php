<?php

/**
 * @file
 * Contains \Drupal\social_counters\Plugin\SocialCounters\Instagram.
 */

namespace Drupal\social_counters\Plugin\SocialCounters;

use Drupal\Core\Url;
use Drupal\social_counters\Plugin\SocialCountersPluginBase;

/**
 * Provides a Instagram social counters plugin.
 * @Plugin(
 *   id = "social_counters_instagram",
 *   label = @Translation("Instagram"),
 * )
 */
class Instagram extends SocialCountersPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getCount() {
    $count = 0;
    $config = $this->configuration['config'];

    $url = Url::fromUri('https://api.instagram.com/v1/users/' . $config->id, array('query' => array(
      'access_token' => $config->token,
    )))->toString();

    try {
      $response = $this->http_client->request('GET', $url);
      $result = $this->json_serializer->decode((string)$response->getBody());
      if ($response->getStatusCode() == 200 && !isset($result['error'])) {
        $count = $result['data']['counts']['followed_by'];
      }
      else {
        $this->logger->warning('%message', array('%message' => $result['error']));
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
    return $this->t('Instagram');
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(&$form, &$form_state, $config) {
    $form['id'] = array(
      '#title' => $this->t('Instagram Id'),
      '#description' => $this->t('Instagram Id. Pay attention that Instagram username isn\'t valid. Example: 249655166'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => !empty($config->id) ? $config->id : '',
    );

    $form['token'] = array(
      '#title' => $this->t('Access Token'),
      '#description' => t('You can read how to generate Instagram Access Token at  <a href="@dev-instagram">instagram.com</a>.', array(
          '@dev-instagram' => Url::fromUri('https://instagram.com/developer/authentication/')->toString()
        )),
      '#type' => 'textfield',
      '#maxlength' => 256,
      '#required' => TRUE,
      '#default_value' => !empty($config->token) ? $config->token : '',
    );
  }
}
