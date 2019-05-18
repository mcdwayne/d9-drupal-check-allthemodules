<?php

namespace Drupal\akamai\Plugin\Client;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Form\FormStateInterface;
use Drupal\akamai\AkamaiClientBase;
use GuzzleHttp\Exception\RequestException;

/**
 * Defines the CCUv2 client version for Akamai.
 *
 * @AkamaiClient(
 *   id = "v2",
 *   title = @Translation("Akamai Client CCUv2")
 * )
 */
class AkamaiClientV2 extends AkamaiClientBase {

  /**
   * Represents whether this client uses a queuing system or not.
   *
   * @var bool
   */
  protected $usesQueue = TRUE;

  /**
   * The queue name to clear.
   *
   * @var string
   */
  protected $queue = 'default';

  /**
   * Checks that we can connect with the supplied credentials.
   *
   * @return bool
   *   TRUE if authorised, FALSE if not.
   */
  public function isAuthorized() {
    try {
      $response = $this->doGetQueue();
    }
    catch (RequestException $e) {
      // @todo better handling
      $this->logger->error($this->formatExceptionMessage($e));
      return FALSE;
    }
    return $response->getStatusCode() == 200;
  }

  /**
   * Ask the API to purge an object.
   *
   * @param string[] $objects
   *   A non-associative array of Akamai objects to clear.
   *
   * @return \GuzzleHttp\Psr7\Response|bool
   *   Response to purge request, or FALSE on failure.
   *
   * @link https://developer.akamai.com/api/purge/ccu/reference.html
   * @link https://github.com/akamai-open/api-kickstart/blob/master/examples/php/ccu.php#L58
   */
  protected function purgeRequest(array $objects) {
    try {
      $response = $this->client->request(
        'POST',
        $this->apiBaseUrl . 'queues/' . $this->queue,
        ['json' => $this->createPurgeBody($objects)]
      );
      // Note that the response has useful data that we need to record.
      // Example response body:
      // @code
      // {
      //  "estimatedSeconds": 420,
      //  "progressUri": "/ccu/v2/purges/57799d8b-10e4-11e4-9088-62ece60caaf0",
      //  "purgeId": "57799d8b-10e4-11e4-9088-62ece60caaf0",
      //  "supportId": "17PY1405953363409286-284546144",
      //  "httpStatus": 201,
      //  "detail": "Request accepted.",
      //  "pingAfterSeconds": 420
      //  }.
      // @endcode
      $this->statusStorage->saveResponseStatus($response, $objects);
      return $response;
    }
    catch (RequestException $e) {
      $this->logger->error($this->formatExceptionMessage($e));
      return FALSE;
      // @todo better error handling
      // Throw $e;.
    }
  }

  /**
   * Get a queue to check its status.
   *
   * @param string $queue_name
   *   The queue name to check. Defaults to 'default'.
   *
   * @return array
   *   Response body of request as associative array.
   *
   * @link https://api.ccu.akamai.com/ccu/v2/docs/#section_CheckingQueueLength
   * @link https://developer.akamai.com/api/purge/ccu/reference.html
   */
  public function getQueue($queue_name = 'default') {
    return Json::decode($this->doGetQueue($queue_name)->getBody());
  }

  /**
   * Gets the raw Guzzle result of checking a queue.
   *
   * We use this to check connectivity, which is why it is broken out into a
   * private function.
   *
   * @param string $queue_name
   *   The queue name to check. Defaults to 'default'.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The HTTP response.
   */
  private function doGetQueue($queue_name = 'default') {
    return $this->client->get($this->apiBaseUrl . "queues/{$queue_name}");
  }

  /**
   * Get the number of items remaining in the purge queue.
   *
   * @return int
   *   A count of the remaining items in the purge queue.
   */
  public function getQueueLength() {
    return $this->getQueue()['queueLength'];
  }

  /**
   * Sets the queue name.
   *
   * @param string $queue
   *   The queue name.
   *
   * @return $this
   */
  public function setQueue($queue) {
    $this->queue = $queue;
    return $this;
  }

  /**
   * Helper function to validate the actions for purge request.
   *
   * @return $this
   */
  public function validActions() {
    return ['remove', 'invalidate'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $default_action = key(array_filter($this->configFactory->get('akamai.settings')->get('action_v2')));
    $form['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Clearing Action Type Default'),
      '#default_value' => in_array($default_action, $this->validActions()) ? $default_action : 'remove',
      '#options' => [
        'remove' => $this->t('Remove'),
        'invalidate' => $this->t('Invalidate'),
      ],
      '#description' => $this->t('The default clearing action. The options are <em>remove</em> (which removes the item from the Akamai cache) and <em>invalidate</em> (which leaves the item in the cache, but invalidates it so that the origin will be hit on the next request).'),
      '#required' => TRUE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $actions = array_fill_keys($this->validActions(), FALSE);
    $actions[$form_state->getValue(['v2', 'action'])] = TRUE;

    $this->configFactory->getEditable('akamai.settings')
      ->set('action_v2', $actions)
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public static function getSupportedVersions() {
    $versions = [];
    foreach (static::supportedTypes() as $type) {
      $versions[] = Unicode::strtolower($type);
    }
    return $versions;
  }

}
