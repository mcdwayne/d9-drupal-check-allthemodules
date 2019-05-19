<?php

/**
 * @file
 * Contains \Drupal\social_counters\Plugin\SocialCounters\Soundcloud.
 */

namespace Drupal\social_counters\Plugin\SocialCounters;

use Drupal\Core\Url;
use Drupal\social_counters\Plugin\SocialCountersPluginBase;

/**
 * Provides a Soundcloud social counters plugin.
 * @Plugin(
 *   id = "social_counters_soundcloud",
 *   label = @Translation("Soundcloud"),
 * )
 */
class Soundcloud extends SocialCountersPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getCount() {
    $count = 0;
    $config = $this->configuration['config'];

    $url = Url::fromUri('http://api.soundcloud.com/users/' . $config->id, array('query' => array(
      'client_id' => $config->client_id,
    )))->toString();

    try {
      $response = $this->http_client->request('GET', $url);
      $result = $this->json_serializer->decode((string)$response->getBody());
      if (!empty(($result))) {
        $count = $result['followers_count'];
      }
      else {
        $this->logger->warning($this->t("Couldn't retrieve data for Soundcloud."));
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
    return $this->t('Soundcloud');
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(&$form, &$form_state, $config) {
    $form['id'] = array(
      '#title' => $this->t("Soundcloud user's id or name."),
      '#description' => $this->t("Soundcloud user's id or name. Examples: 'happymusic' or '303027'."),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => !empty($config->id) ? $config->id : '',
    );

    $form['client_id'] = array(
      '#title' => $this->t("The client id."),
      '#description' => $this->t('The client id belonging to your application. You can find it on <a href="@soundcloud-apps">Your Applications page</a>.', array(
        '@soundcloud-apps' => Url::fromUri('http://soundcloud.com/you/apps')->toString(),
        )),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => !empty($config->client_id) ? $config->client_id : '',
    );
  }
}
