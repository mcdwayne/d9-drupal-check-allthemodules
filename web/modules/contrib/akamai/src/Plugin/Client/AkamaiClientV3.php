<?php

namespace Drupal\akamai\Plugin\Client;

use Drupal\Core\Form\FormStateInterface;
use Drupal\akamai\AkamaiClientBase;
use GuzzleHttp\Exception\RequestException;

/**
 * Defines the CCUv3 client version for Akamai.
 *
 * @AkamaiClient(
 *   id = "v3",
 *   title = @Translation("Akamai Client CCUv3")
 * )
 */
class AkamaiClientV3 extends AkamaiClientBase {

  /**
   * Base url to which API method names are appended.
   *
   * @var string
   */
  protected $apiBaseUrl = '/ccu/v3/';

  /**
   * An action to take, either 'delete' or 'invalidate'.
   *
   * @var string
   */
  protected $action = 'delete';

  /**
   * Type of purge, either 'url', 'tag' or 'cpcode'.
   *
   * @var string
   */
  protected $type = 'url';

  /**
   * Represents whether this client uses a queuing system or not.
   *
   * @var bool
   */
  protected $usesQueue = FALSE;

  /**
   * Checks that we can connect with the supplied credentials.
   *
   * @return bool
   *   TRUE if authorised, FALSE if not.
   */
  public function isAuthorized() {
    return TRUE;
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
        "{$this->apiBaseUrl}{$this->action}/{$this->type}/{$this->domain}",
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
   * Create an array to pass to Akamai's purge function.
   *
   * @param string[] $objects
   *   A list of URLs.
   *
   * @return array
   *   An array suitable for sending to the Akamai purge endpoint.
   */
  public function createPurgeBody(array $objects) {
    $body = [
      'objects' => $objects,
    ];
    if ($this->type == 'url') {
      $purge_urls_with_hostname = $this->configFactory
        ->get('akamai.settings')
        ->get('purge_urls_with_hostname');
      if ($purge_urls_with_hostname) {
        $body['hostname'] = $this->baseUrl;
      }
    }
    return (object) $body;
  }

  /**
   * Helper function to validate the actions for purge request.
   *
   * @return $this
   */
  public function validActions() {
    return ['delete', 'invalidate'];
  }

  /**
   * Sets the type of purge.
   *
   * @param string $type
   *   The type of purge, either 'url', 'tag' or 'cpcode'.
   *
   * @return $this
   */
  public function setType($type) {
    $valid_types = ['cpcode', 'tag', 'url'];
    if (in_array($type, $valid_types)) {
      $this->type = $type;
    }
    else {
      throw new \InvalidArgumentException('Type must be one of: ' . implode(', ', $valid_types));
    }
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $config = $this->configFactory->get('akamai.settings');
    $default_action = key(array_filter($config->get('action_v3')));
    $form['action'] = [
      '#type' => 'select',
      '#title' => $this->t('Clearing Action Type Default'),
      '#default_value' => in_array($default_action, $this->validActions()) ? $default_action : 'delete',
      '#options' => [
        'delete' => $this->t('Delete'),
        'invalidate' => $this->t('Invalidate'),
      ],
      '#description' => $this->t('The default clearing action. The options are <em>delete</em> (which deletes the item from the Akamai cache) and <em>invalidate</em> (which leaves the item in the cache, but invalidates it so that the origin will be hit on the next request).'),
      '#required' => TRUE,
    ];
    $form['purge_urls_with_hostname'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Purge URLs With Common Hostname'),
      '#default_value' => $config->get('purge_urls_with_hostname'),
      '#description' => $this->t('Sends Base Path as "hostname" Fast Purge API request data member when purging URLs'),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $actions = array_fill_keys($this->validActions(), FALSE);
    $actions[$form_state->getValue(['v3', 'action'])] = TRUE;

    $this->configFactory->getEditable('akamai.settings')
      ->set('action_v3', $actions)
      ->set('purge_urls_with_hostname', $form_state->getValue('purge_urls_with_hostname'))
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
