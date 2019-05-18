<?php

namespace Drupal\brightcove\Entity;

use Brightcove\API\Request\SubscriptionRequest;
use Brightcove\Object\Subscription;
use Drupal\brightcove\BrightcoveAPIClientInterface;
use Drupal\brightcove\BrightcoveUtil;
use Drupal\brightcove\Entity\Exception\BrightcoveSubscriptionException;

/**
 * Defines the Brightcove Subscription entity.
 */
class BrightcoveSubscription implements BrightcoveSubscriptionInterface {

  /**
   * Internal BrightcoveSubscription ID of the entity.
   *
   * @var int
   */
  protected $id;

  /**
   * Brightcove Subscription ID of the entity.
   *
   * @var string
   */
  protected $bcsid;

  /**
   * Status of the Subscription.
   *
   * @var bool
   */
  protected $status = TRUE;

  /**
   * Indicates default subscription for the client.
   *
   * @var bool
   */
  protected $default = FALSE;

  /**
   * The Brightcove API Client.
   *
   * @var \Drupal\brightcove\Entity\BrightcoveAPIClient
   */
  protected $apiClient;

  /**
   * The notifications endpoint.
   *
   * @var string
   */
  protected $endpoint;

  /**
   * Array of events subscribed to.
   *
   * @var string[]
   */
  protected $events;

  /**
   * Drupal database connection.
   *
   * @var \Drupal\core\Database\Connection
   */
  protected $connection;

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    if ($this->default) {
      return $this->status;
    }
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function isDefault() {
    return $this->default;
  }

  /**
   * {@inheritdoc}
   */
  public function isNew() {
    return empty($this->id);
  }

  /**
   * {@inheritdoc}
   */
  public function getApiClient() {
    return !empty($this->apiClient) ? $this->apiClient : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getBcSid() {
    return $this->bcsid;
  }

  /**
   * {@inheritdoc}
   */
  public function getEndpoint() {
    return $this->endpoint;
  }

  /**
   * {@inheritdoc}
   */
  public function getEvents() {
    return $this->events;
  }

  /**
   * {@inheritdoc}
   */
  public function getId() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function setApiClient($apiClient) {
    $this->apiClient = $apiClient;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setBcSid($bcsid) {
    $this->bcsid = $bcsid;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEndpoint($endpoint) {
    $this->endpoint = $endpoint;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setEvents(array $events) {
    $this->events = $events;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setStatus($status) {
    if ($this->default) {
      $this->status = $status;
      return $this;
    }
    throw new \Exception('Not possible to set status of a non-default Subscription.');
  }

  /**
   * Initializes the BrightcoveSubscription Entity object.
   *
   * @param bool $is_default
   *   Whether this subscription should be default or not. There is be only one
   *   per API client.
   */
  public function __construct($is_default = FALSE) {
    $this->id = NULL;
    $this->default = $is_default;
    $this->connection = \Drupal::getContainer()->get('database');
  }

  /**
   * Loads the entity by a given field and value.
   *
   * @param string $field
   *   The name of the field.
   * @param string|int $value
   *   The field's value that needs to be checked to get a specific
   *   subscription.
   *
   * @return \Drupal\brightcove\Entity\BrightcoveSubscription|null
   *   The default Brightcove Subscription for the given API client or NULL if
   *   not found.
   *
   * @throws \Drupal\brightcove\Entity\Exception\BrightcoveSubscriptionException
   *   If the field is not valid.
   */
  protected static function loadByField($field, $value) {
    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = \Drupal::getContainer()
      ->get('database');

    $query = $connection->select('brightcove_subscription', 'bs')
      ->fields('bs');

    switch ($field) {
      case 'bcsid':
        $query->condition('bs.bcsid', $value);
        break;

      case 'default':
        $query->condition('bs.api_client_id', $value)
          ->condition('bs.is_default', 1);
        break;

      case 'endpoint':
        $query->condition('bs.endpoint', $value);
        break;

      case 'id':
        $query->condition('bs.id', $value);
        break;

      default:
        throw new BrightcoveSubscriptionException('Invalid field type.');
    }

    $result = $query->execute()
      ->fetchAssoc();

    if (empty($result)) {
      $result = [];
    }
    else {
      // Unserialize events.
      $result['events'] = unserialize($result['events']);
    }

    return self::createFromArray($result);
  }

  /**
   * Loads the default subscription by API Client ID.
   *
   * @param \Drupal\brightcove\Entity\BrightcoveAPIClient $api_client
   *   Loaded API Client entity.
   *
   * @return \Drupal\brightcove\Entity\BrightcoveSubscription|null
   *   The default Brightcove Subscription for the given API client or NULL if
   *   not found.
   *
   * @throws \Drupal\brightcove\Entity\Exception\BrightcoveSubscriptionException
   */
  public static function loadDefault(BrightcoveAPIClient $api_client) {
    return self::loadByField('default', $api_client->id());
  }

  /**
   * Loads the entity by it's internal Drupal ID.
   *
   * @param int $id
   *   The internal Drupal ID of the entity.
   *
   * @return \Drupal\brightcove\Entity\BrightcoveSubscription|null
   *   Loaded BrightcoveSubscription entity, or NULL if not found.
   *
   * @throws \Drupal\brightcove\Entity\Exception\BrightcoveSubscriptionException
   */
  public static function load($id) {
    return self::loadByField('id', $id);
  }

  /**
   * Loads multiple BrightcoveSubscription entities.
   *
   * @param string[] $order_by
   *   Fields to order by:
   *     - key: the name of the field.
   *     - value: the order direction.
   *
   * @return \Drupal\brightcove\Entity\BrightcoveSubscription[]
   *   Returns loaded Brightcove Subscription entity objects keyed by ID or an
   *   empty array if there are none.
   */
  public static function loadMultiple(array $order_by = ['is_default' => 'DESC', 'endpoint' => 'ASC']) {
    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = \Drupal::getContainer()
      ->get('database');

    $query = $connection->select('brightcove_subscription', 'bs')
      ->fields('bs');

    // Set orders.
    foreach ($order_by as $field => $direction) {
      $query->orderBy($field, $direction);
    }

    $brightcove_subscriptions = $query->execute()
      ->fetchAllAssoc('id', \PDO::FETCH_ASSOC);

    $loaded_brightcove_subscriptions = [];
    foreach ($brightcove_subscriptions as $id => $brightcove_subscription) {
      $brightcove_subscription['events'] = unserialize($brightcove_subscription['events']);
      $loaded_brightcove_subscriptions[$id] = BrightcoveSubscription::createFromArray($brightcove_subscription);
    }
    return $loaded_brightcove_subscriptions;
  }

  /**
   * Load Subscriptions for a given API client.
   *
   * @param \Drupal\brightcove\Entity\BrightcoveAPIClient $api_client
   *   Loaded API client.
   *
   * @return \Drupal\brightcove\Entity\BrightcoveSubscription[]
   *   Returns loaded Brightcove Subscription entity objects keyed by ID or an
   *   empty array if there are none.
   */
  public static function loadMultipleByApiClient(BrightcoveAPIClient $api_client) {
    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = \Drupal::getContainer()
      ->get('database');

    $brightcove_subscriptions = $connection->select('brightcove_subscription', 'bs')
      ->fields('bs')
      ->condition('api_client_id', $api_client->id())
      ->execute()
      ->fetchAllAssoc('id', \PDO::FETCH_ASSOC);

    $loaded_brightcove_subscriptions = [];
    foreach ($brightcove_subscriptions as $id => $brightcove_subscription) {
      $brightcove_subscription['events'] = unserialize($brightcove_subscription['events']);
      $loaded_brightcove_subscriptions[$id] = BrightcoveSubscription::createFromArray($brightcove_subscription);
    }
    return $loaded_brightcove_subscriptions;
  }

  /**
   * Loads entity by it's Brightcove Subscription ID.
   *
   * @param string $bcsid
   *   Brightcove ID of the subscription.
   *
   * @return \Drupal\brightcove\Entity\BrightcoveSubscription|null
   *   Loaded BrightcoveSubscription entity, or NULL if not found.
   *
   * @throws \Drupal\brightcove\Entity\Exception\BrightcoveSubscriptionException
   */
  public static function loadByBcSid($bcsid) {
    return self::loadByField('bcsid', $bcsid);
  }

  /**
   * Load a Subscription by its endpoint.
   *
   * @param string $endpoint
   *   The endpoint.
   *
   * @return \Drupal\brightcove\Entity\BrightcoveSubscription|null
   *   The Subscription with the given endpoint or NULL if not found.
   *
   * @throws \Drupal\brightcove\Entity\Exception\BrightcoveSubscriptionException
   */
  public static function loadByEndpoint($endpoint) {
    return self::loadByField('endpoint', $endpoint);
  }

  /**
   * Creates a BrightcoveSubscription entity from an array.
   *
   * @param array $data
   *   Array that contains information about the entity.
   *   Values:
   *     - id (int): Internal Drupal identifier, it will be ignored when saving
   *                 the entity.
   *     - bcsid (string): Brightcove Subscription entity identifier.
   *     - api_client_id (string): API Client ID.
   *     - endpoint (string): Endpoint callback URL, required.
   *     - events (string[]): Events list, eg.: video-change, required.
   *     - is_default (bool): Whether the current Brightcove Subscription is
   *                          default or not. Will be ignored for local entity
   *                          update.
   *     - status (bool): Indicates whether a subscription is enabled or
   *                      disabled. An existing non-default subscription is
   *                      always enabled, only default subscriptions can be set
   *                      to disabled.
   *
   * @return \Drupal\brightcove\Entity\BrightcoveSubscription|null
   *   The initialized BrightcoveSubscription entity object, or null if the
   *   $data array is empty.
   */
  public static function createFromArray(array $data) {
    if (!empty($data) && !empty($data['api_client_id'])) {
      $api_client = BrightcoveAPIClient::load($data['api_client_id']);
      $brightcove_subscription = (new BrightcoveSubscription())
        ->setApiClient($api_client)
        ->setEndpoint($data['endpoint'])
        ->setEvents($data['events']);

      if (isset($data['id'])) {
        $brightcove_subscription->id = (int) $data['id'];
      }
      if (isset($data['bcsid'])) {
        $brightcove_subscription->bcsid = $data['bcsid'];
      }
      if (isset($data['is_default'])) {
        $brightcove_subscription->default = (bool) $data['is_default'];
      }
      if (isset($data['status'])) {
        $brightcove_subscription->status = (bool) $data['status'];
      }

      return $brightcove_subscription;
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   *
   * @param bool $upload
   *   Whether to upload the new subscription to Brightcove or not.
   */
  public function save($upload = FALSE) {
    // Fields to insert or update.
    $fields = [
      'api_client_id' => $this->getApiClient()->id(),
      'endpoint' => $this->getEndpoint(),
      'events' => serialize($this->getEvents()),
    ];
    $fields += ['bcsid' => !empty($this->bcsid) ? $this->getBcSid() : NULL];
    $fields += ['status' => $this->isDefault() ? (int) $this->isActive() : 1];

    // Save new entity.
    if ($this->isNew()) {
      // Try to get a default subscription.
      $default_subscription = self::loadDefault($this->apiClient);
      $default_endpoint = BrightcoveUtil::getDefaultSubscriptionUrl();

      // Check whether we already have a default subscription for the API client
      // and throw an exception if one already exists.
      if ($this->isDefault() && !empty($default_subscription)) {
        throw new BrightcoveSubscriptionException(strtr('Default subscription already exists for the :api_client API Client.', [
          ':api_client' => $this->apiClient->getLabel(),
        ]));
      }
      // Otherwise if the API Client does not have a default subscription and
      // the site's URL matches the subscription's endpoint that needs to be
      // created, then make it default.
      elseif (empty($default_subscription) && $this->getEndpoint() == $default_endpoint) {
        $this->default = TRUE;
      }

      // Create subscription on Brightcove only if the entity is new, as for now
      // it is not possible to update existing subscriptions.
      if ($upload) {
        $this->saveToBrightcove();
      }

      // Insert Brightcove Subscription into the database.
      $this->connection->insert('brightcove_subscription')
        ->fields($fields + ['is_default' => (int) $this->isDefault()])
        ->execute();
    }
    // Allow local changes to be saved.
    elseif (!$upload) {
      $this->connection->update('brightcove_subscription')
        ->fields($fields)
        ->condition('id', $this->getId())
        ->execute();
    }
    else {
      throw new BrightcoveSubscriptionException('An already existing subscription cannot be updated!');
    }
  }

  /**
   * Saves the subscription entity to Brightcove.
   *
   * @throws \Drupal\brightcove\Entity\Exception\BrightcoveSubscriptionException
   *   If the Subscription wasn't saved to Brightcove successfully.
   */
  public function saveToBrightcove() {
    try {
      // Get CMS API.
      $cms = BrightcoveUtil::getCmsApi($this->apiClient->id());

      if ($is_default = $this->isDefault()) {
        // Make sure that when the default is enabled, always use the correct
        // URL.
        $default_endpoint = BrightcoveUtil::getDefaultSubscriptionUrl();
        if ($this->endpoint != $default_endpoint) {
          $this->setEndpoint($default_endpoint);
        }
      }

      // Create subscription.
      $subscription_request = new SubscriptionRequest();
      $subscription_request->setEndpoint($this->getEndpoint());
      $subscription_request->setEvents($this->getEvents());
      $new_subscription = $cms->createSubscription($subscription_request);
      $this->setBcSid($new_subscription->getId());

      // If it's a default subscription update the local entity to enable it.
      if ($is_default) {
        $this->setStatus(TRUE);
        $this->save();
      }
    }
    catch (\Exception $e) {
      watchdog_exception('brightcove', $e, $e->getMessage());
      throw new BrightcoveSubscriptionException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @param bool $local_only
   *   If TRUE delete the local Subscription entity only, otherwise delete the
   *   subscription from Brightcove as well.
   */
  public function delete($local_only = TRUE) {
    $this->connection->delete('brightcove_subscription')
      ->condition('id', $this->id)
      ->execute();

    if (!$local_only) {
      $this->deleteFromBrightcove();
    }
  }

  /**
   * Delete the Subscription from Brightcove only.
   *
   * @throws \Drupal\brightcove\Entity\Exception\BrightcoveSubscriptionException
   * @throws \Exception
   */
  public function deleteFromBrightcove() {
    try {
      $cms = BrightcoveUtil::getCmsApi($this->apiClient->id());
      $cms->deleteSubscription($this->getBcSid());
    }
    catch (\Exception $e) {
      // In case of the subscription cannot be found on Brightcove, just ignore,
      // otherwise throw an exception.
      if ($e->getCode() != 404) {
        $message = 'Failed to delete Subscription with @endpoint endpoint (ID: @bcsid).';
        $replacement = [
          '@endpoint' => $this->getEndpoint(),
          '@bcsid' => $this->getBcSid(),
        ];

        watchdog_exception('brightcove', $e, $message, $replacement);
        throw new BrightcoveSubscriptionException(strtr($message, $replacement), $e->getCode(), $e);
      }
    }

    // In case of a default subscription set status to disabled and unset the
    // Brightcove ID.
    if ($this->isDefault()) {
      $this->setBcSid(NULL);
      $this->setStatus(FALSE);
      $this->save();
    }
  }

  /**
   * Create or update a Subscription entity.
   *
   * @param \Brightcove\Object\Subscription $subscription
   *   Subscription object from Brightcove.
   * @param \Drupal\brightcove\Entity\BrightcoveAPIClient|null $api_client
   *   Loaded API client entity, or null.
   *
   * @throws \Drupal\brightcove\Entity\Exception\BrightcoveSubscriptionException
   * @throws \Exception
   */
  public static function createOrUpdate(Subscription $subscription, BrightcoveAPIClient $api_client = NULL) {
    /** @var \Drupal\brightcove\Entity\BrightcoveSubscription $brightcove_subscription */
    $brightcove_subscription = self::loadByEndpoint($subscription->getEndpoint());

    // If there is no Subscription by the endpoint, try to get one by its ID.
    if (empty($brightcove_subscription)) {
      /** @var \Drupal\brightcove\Entity\BrightcoveSubscription $subscription */
      $brightcove_subscription = self::loadByBcSid($subscription->getId());
    }

    // Create new subscription if needed.
    if (empty($brightcove_subscription)) {
      $brightcove_subscription = new BrightcoveSubscription();
      $brightcove_subscription->bcsid = $subscription->getId();

      /** @var \Drupal\brightcove\Entity\BrightcoveAPIClient $api_client */
      if (!empty($api_client)) {
        $brightcove_subscription->setApiClient($api_client);
      }
      else {
        return;
      }
    }

    $needs_save = FALSE;

    // Update ID.
    if (($bcsid = $subscription->getId()) != $brightcove_subscription->getBcSid()) {
      $brightcove_subscription->setBcSid($bcsid);
      $needs_save = TRUE;
    }

    // In case of an inactive default subscription set status to TRUE.
    if ($brightcove_subscription->isDefault() && !$brightcove_subscription->isActive()) {
      $brightcove_subscription->setStatus(TRUE);
      $needs_save = TRUE;
    }

    // Update endpoint.
    if (($endpoint = $subscription->getEndpoint()) != $brightcove_subscription->getEndpoint()) {
      $brightcove_subscription->setEndpoint($endpoint);
      $needs_save = TRUE;
    }

    // Update events.
    $events = $subscription->getEvents();
    if (!is_array($events)) {
      $events = [$events];
    }
    if ($events != $brightcove_subscription->getEvents()) {
      $brightcove_subscription->setEvents($events);
      $needs_save = TRUE;
    }

    // Save the Subscription if needed.
    if ($needs_save) {
      $brightcove_subscription->save(FALSE);
    }
  }

  /**
   * Counts local subscriptions.
   *
   * @return int|null
   *   Number of the available local subscriptions entities.
   */
  public static function count() {
    /** @var \Drupal\Core\Database\Connection $connection */
    $connection = \Drupal::getContainer()
      ->get('database');

    return $connection->select('brightcove_subscription', 'bs')
      ->fields('bs')
      ->countQuery()
      ->execute()
      ->fetchField();
  }

  /**
   * Get all available subscriptions from Brightcove.
   *
   * @param \Drupal\brightcove\BrightcoveAPIClientInterface $api_client
   *   API Client entity.
   *
   * @return \Brightcove\Object\Subscription[]
   *   List of subscriptions or null of there are none.
   */
  public static function listFromBrightcove(BrightcoveAPIClientInterface $api_client) {
    $subscriptions = &drupal_static(__FUNCTION__);
    if (is_null($subscriptions)) {
      $cms = BrightcoveUtil::getCmsApi($api_client->id());
      $subscriptions = $cms->getSubscriptions();
    }
    return $subscriptions;
  }

}
