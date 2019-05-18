<?php

namespace Drupal\aegir_site_subscriptions\Services;

use Drupal\aegir_site_subscriptions\Exceptions\SubscriptionServiceMissingSubscriptionException;
use Drupal\aegir_site_subscriptions\Plugin\SubscriptionProviderManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;

/**
 * The subscription service providing additional functionality to its nodes.
 */
class Subscription {

  /**
   * The subscription.
   *
   * @var object
   */
  protected $subscription;

  /**
   * The subscription provider manager.
   *
   * @var \Drupal\aegir_site_subscriptions\Plugin\SubscriptionProviderManager
   */
  protected $subscriptionProviderManager;

  /**
   * Node storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $nodeStorage;

  /**
   * This module's configuration.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $moduleConfig;

  /**
   * Class Constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   A storage instance.
   * @param \Drupal\aegir_site_subscriptions\Plugin\SubscriptionProviderManager $subscription_provider_manager
   *   * The subscription provider manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   This module's configuration.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
      EntityTypeManager $entity_type_manager,
      SubscriptionProviderManager $subscription_provider_manager,
      ConfigFactory $config_factory
  ) {
    $this->nodeStorage = $entity_type_manager->getStorage('node');
    $this->subscriptionProviderManager = $subscription_provider_manager;
    $this->moduleConfig = $config_factory->get('aegir_site_subscriptions.settings');

    $this->subscription = NULL;
  }

  /**
   * Associates the service with a particular subscription.
   *
   * It's necessary to call this method before any other non-static methods.
   *
   * @param object $subscription
   *   The subscription object.
   *
   * @return $this
   *   The object itself, for method chaining.
   */
  public function setSubscription($subscription) {
    $this->subscription = $subscription;
    return $this;
  }

  /**
   * Associates the service with a particular subscription, based on ID.
   *
   * @param string $subscription_id
   *   The subscription ID.
   *
   * @return $this
   *   The object itself, for method chaining.
   */
  public function setSubscriptionById($subscription_id) {
    if (empty($subscription_id)) {
      return NULL;
    }

    try {
      $subscription = $this->subscriptionProviderManager
        ->createInstance($this->getSubscriptionProviderPluginId())
        ->getSubscriptionInstance($subscription_id);
    }
    catch (\Exception $e) {
      return NULL;
    }

    return is_object($subscription) ? $this->setSubscription($subscription) : NULL;
  }

  /**
   * Fetches the subscription currently set with the service.
   *
   * @return object
   *   The subscription object.
   */
  protected function getSubscription() {
    if (is_null($this->subscription)) {
      throw new SubscriptionServiceMissingSubscriptionException('This operation requires that the subscription service be set with a subscription.');
    }
    return $this->subscription;
  }

  /**
   * Gets the subscription URL from an ID.
   *
   * @return string
   *   The subscription URL.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getUrl() {
    return $this->subscriptionProviderManager
      ->createInstance($this->getSubscriptionProviderPluginId())
      ->getSubscriptionUrl($this->getId());
  }

  /**
   * Creates a site record.
   *
   * @param int $user_id
   *   The ID of the user whose site is being created.
   *
   * @return \Drupal\node\Entity\Node
   *   The new site.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createSite($user_id) {
    $subscription_id = $this->getId();

    $site = Node::create([
      'type' => 'aegir_site',
      // This is temporary until we get the user's preferred site name.
      'title' => $subscription_id,
      'uid' => $user_id,
      'field_site_subscription_url' => [
        'title' => $subscription_id,
        'uri' => $this->getUrl(),
      ],
    ]);

    $site->save();
    return $site;
  }

  /**
   * Fetches the list of add-ons.
   *
   * @return array
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *
   * @see hook_aegir_site_subscriptions_quota_info()
   */
  public function getAddons() {
    return $this->subscriptionProviderManager
      ->createInstance($this->getSubscriptionProviderPluginId())
      ->getSubscriptionAddons($this->getSubscription());
  }

  /**
   * Fetches the list of site IDs associated with this subscription.
   *
   * @param int $user_id
   *   The local Drupal user ID.
   *
   * @return array
   *   The list of site IDs.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getSiteIds($user_id) {
    return $this->nodeStorage->getQuery()
      ->condition('type', 'aegir_site')
      ->condition('uid', $user_id)
      ->condition('field_site_subscription_url.title', $this->getId())
      ->execute();
  }

  /**
   * Fetches the ID.
   *
   * @return string|null
   *   The ID or NULL if there isn't one.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getId() {
    return $this->subscriptionProviderManager
      ->createInstance($this->getSubscriptionProviderPluginId())
      ->getSubscriptionId($this->getSubscription());
  }

  /**
   * Returns a Drupal user ID corresponding with one in the subscription system.
   *
   * @param string $subscription_provider_user_id
   *   The user ID in the subscriptions system.
   *
   * @return int
   *   The Drupal user ID.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getDrupalUserId($subscription_provider_user_id) {
    return $this->subscriptionProviderManager
      ->createInstance($this->getSubscriptionProviderPluginId())
      ->getDrupalUserId($subscription_provider_user_id);
  }

  /**
   * Fetches a site corresponding to the subscription if it's active.
   *
   * @param string $account_code
   *   The remote account code.
   *
   * @return \Drupal\node\Entity\Node|null
   *   The site node or NULL if there isn't a site with an active subscription.
   *
   * @throws \Exception
   */
  public function getSiteIfSubscriptionIsActive($account_code) {
    if (($site = $this->getSite($account_code)) && $this->isActive()) {
      return $site;
    }
    return NULL;
  }

  /**
   * Fetches a site corresponding to the subscription.
   *
   * @param string $account_code
   *   The remote account code.
   *
   * @return \Drupal\node\Entity\Node|null
   *   The site node or NULL if there isn't an associated site.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getSite($account_code) {
    $site_ids = $this->getSiteIds($this->getDrupalUserId($account_code));
    if (empty($site_ids) || (!$site = Node::load(array_pop($site_ids)))) {
      return NULL;
    }
    return $site;
  }

  /**
   * Fetch the plan code.
   *
   * @return string
   *   The plan code.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function getPlanCode() {
    return $this->subscriptionProviderManager
      ->createInstance($this->getSubscriptionProviderPluginId())
      ->getSubscriptionPlanCode($this->getSubscription());
  }

  /**
   * Determines if the subscription is active or not.
   *
   * @return bool
   *   TRUE if the subscription is active; FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function isActive() {
    return $this->subscriptionProviderManager
      ->createInstance($this->getSubscriptionProviderPluginId())
      ->subscriptionIsActive($this->getSubscription());
  }

  /**
   * Fetches the plugin ID for the active subscription provider.
   *
   * @return string
   */
  protected function getSubscriptionProviderPluginId() {
    if (!$provider_plugin_id = $this->moduleConfig->get('subscription_provider')) {
      // @todo Report emergency: no subscription provider selected.
    }
    return $provider_plugin_id;
  }

}
