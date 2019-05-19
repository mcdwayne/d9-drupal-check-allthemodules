<?php

/**
 * @file
 * Contains \Drupal\social_counters\Plugin\SocialCounters\Flickr.
 */

namespace Drupal\social_counters\Plugin\SocialCounters;

use Drupal\Core\Url;
use Drupal\social_counters\Plugin\SocialCountersPluginBase;

/**
 * Provides a Flickr social counters plugin.
 * @Plugin(
 *   id = "social_counters_flickr",
 *   label = @Translation("Flickr"),
 * )
 */
class Flickr extends SocialCountersPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getCount() {
    $count = 0;
    $config = $this->configuration['config'];

    $url = Url::fromUri('https://api.flickr.com/services/rest/', array('query' => array(
      'group_path_alias' => $config->id,
      'api_key' => $config->api_key,
      'method' => 'flickr.groups.getInfo',
      'format' => 'json',
      'nojsoncallback' => 1,
    )))->toString();

    try {
      $response = $this->http_client->request('GET', $url);
      $result = $this->json_serializer->decode((string)$response->getBody());
      if ($result['stat'] == 'ok') {
        $count = $result['group']['members']['_content'];
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
    return $this->t('Flickr');
  }

  /**
   * {@inheritdoc}
   */
  public function entityForm(&$form, &$form_state, $config) {
    $form['id'] = array(
      '#title' => $this->t('Flickr group name'),
      '#description' => $this->t('Flickr group name. Examples: thecoca-colaco'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => !empty($config->id) ? $config->id : '',
    );

    $form['api_key'] = array(
      '#title' => $this->t('Flickr API Key'),
      '#description' => t('You can generate Flickr API Key at <a href="@dev-flickr">flickr.com</a>.', array(
          '@dev-flickr' => Url::fromUri('https://www.flickr.com/services/apps/create/apply/')->toString(),
        )),
      '#type' => 'textfield',
      '#maxlength' => 256,
      '#required' => TRUE,
      '#default_value' => !empty($config->api_key) ? $config->api_key : '',
    );
  }
}
