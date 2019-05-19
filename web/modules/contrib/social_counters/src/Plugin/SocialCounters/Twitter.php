<?php

/**
 * @file
 * Contains \Drupal\social_counters\Plugin\SocialCounters\Twitter.
 */

namespace Drupal\social_counters\Plugin\SocialCounters;

use Drupal\Core\Url;
use Drupal\social_counters\Plugin\SocialCountersPluginBase;

/**
 * Provides a Twitter social counters plugin.
 * @Plugin(
 *   id = "social_counters_twitter",
 *   label = @Translation("Twitter"),
 * )
 */
class Twitter extends SocialCountersPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getCount() {
    $count = 0;
    $config = $this->configuration['config'];

    $url = Url::fromUri('https://api.twitter.com/1.1/users/show.json', array('query' => array(
      'screen_name' => $config->id,
    )))->toString();

    try {
      $response = $this->http_client->request('GET', $url, array(
        'headers' => array(
          'Authorization' => 'Bearer ' . $config->bearer_token,
        )
      ));

      $result = $this->json_serializer->decode((string)$response->getBody());
      if ($response->getStatusCode() == 200) {
        $count = $result['followers_count'];
      }
      else {
        if (!empty($result['errors'])) {
          foreach ($result['errors'] as $error) {
            $this->logger->warning('%message', array('%message' => $error['message']));
          }
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
    return $this->t('Twitter');
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(&$form, &$form_state, $config) {
    $form['id'] = array(
      '#title' => $this->t('Twitter account'),
      '#description' => $this->t('The Twitter account to pull the number of followers.'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => !empty($config->id) ? $config->id : '',
    );

    $form['bearer_token'] = array(
      '#title' => $this->t('Bearer token(Twitter).'),
      '#description' => t('See documentation about barear token on <a href="@application-only">Application-only authentication</a> page.', array(
        '@application-only' => Url::fromUri('https://dev.twitter.com/oauth/application-only')->toString(),
      )),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#size' => 128,
      '#maxlength' => 256,
      '#default_value' => !empty($config->bearer_token) ? $config->bearer_token : '',
    );
  }
}
