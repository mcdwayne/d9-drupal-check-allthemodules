<?php

/**
 * @file
 * Contains \Drupal\social_counters\Plugin\SocialCounters\Vk.
 */

namespace Drupal\social_counters\Plugin\SocialCounters;

use Drupal\Core\Url;
use Drupal\social_counters\Plugin\SocialCountersPluginBase;

/**
 * Provides a Vk social counters plugin.
 * @Plugin(
 *   id = "social_counters_vk",
 *   label = @Translation("Vk"),
 * )
 */
class Vk extends SocialCountersPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getCount() {
    $count = 0;
    $config = $this->configuration['config'];

    $url = Url::fromUri('https://api.vk.com/method/groups.getById', array('query' => array(
      'group_id' => $config->group_id,
      'count' => 20,
      'v' => 5.21,
      'fields' => 'members_count',
    )))->toString();

    try {
      $response = $this->http_client->request('GET', $url);
      $result = $this->json_serializer->decode((string)$response->getBody());
      if (!isset($result['error'])) {
        $count = reset($result['response'])['members_count'];
      }
      else {
        $this->logger->warning('%message', array('%message' => $result['error']['error_msg']));
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
    return $this->t('Vk');
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(&$form, &$form_state, $config) {
    $form['group_id'] = array(
      '#title' => $this->t('Group id'),
      '#description' => $this->t('The group ID of a VK Group.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => !empty($config->group_id) ? $config->group_id : '',
    );
  }
}
