<?php

/**
 * @file
 * Contains \Drupal\social_counters\Plugin\SocialCounters\Facebook.
 */

namespace Drupal\social_counters\Plugin\SocialCounters;

use Drupal\Core\Url;
use Drupal\social_counters\Plugin\SocialCountersPluginBase;

/**
 * Provides a Facebook social counters plugin.
 * @Plugin(
 *   id = "social_counters_facebook",
 *   label = @Translation("Facebook Social Counter."),
 * )
 */
class Facebook extends SocialCountersPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getCount() {
    $count = 0;
    $config = $this->configuration['config'];

    $url = Url::fromUri('https://graph.facebook.com/' . $config->id, array('query' => array(
      'access_token' => $config->token,
      'fields' => 'likes',
    )))->toString();

    try {
      $response = $this->http_client->request('GET', $url);
      $result = $this->json_serializer->decode((string)$response->getBody());
      if ($response->getStatusCode() == 200 && !isset($result->error)) {
        $count = $result['likes'];
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
    return $this->t('Facebook');
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(&$form, &$form_state, $config) {
    $form['id'] = array(
      '#title' => $this->t('Facebook Page Name (or User ID)'),
      '#description' => $this->t("Either the page name or ID. Examples: 'coca-cola' or '40796308305'."),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => !empty($config->id) ? $config->id : '',
    );

    $form['token'] = array(
      '#title' => $this->t('Facebook Access Token'),
      '#description' => t('See <a href="@facebook-handbook">Access Tokens on Facebook API page</a> and <a href="@stackoverflow-url">detailed explanation on stackoverflow.com</a>.', array(
          '@facebook-handbook' => Url::fromUri('https://developers.facebook.com/docs/facebook-login/access-tokens')->toString(),
          '@stackoverflow-url' => Url::fromUri('http://stackoverflow.com/a/28418469/1391963')->toString(),
        )),
      '#type' => 'textfield',
      '#maxlength' => 256,
      '#required' => TRUE,
      '#default_value' => !empty($config->token) ? $config->token : '',
    );
  }
}
