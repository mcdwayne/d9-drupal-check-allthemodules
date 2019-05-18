<?php

namespace Drupal\recurly_aegir\Wrappers;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Extension\ModuleHandlerInterface;

/**
 * Wrapper for subscriptions providing additional functionality.
 */
class SubscriptionWrapper extends Wrapper {

  /**
   * The subscription.
   *
   * @var Recurly_Subscription
   */
  protected $subscription;

  /**
   * Instantiates a new instance of this class.
   *
   * This is a factory method that returns a new instance of this class. The
   * factory should pass any needed dependencies into the constructor of this
   * class, but not the container itself. Every call to this method must return
   * a new instance of this class; that is, it may not implement a singleton.
   *
   * @param Symfony\Component\DependencyInjection\ContainerInterface $container
   *   The service container this instance should use.
   * @param \Recurly_Subscription $subscription
   *   The subscription.
   *
   * @see ContainerInjectionInterface::create()
   */
  public static function create(ContainerInterface $container, \Recurly_Subscription $subscription) {
    return new static(
      $subscription,
      $container->get('request_stack')->getCurrentRequest(),
      $container->get('entity_type.manager')->getStorage('node'),
      $container->get('config.factory')->get('recurly.settings'),
      $container->get('module_handler')
    );
  }

  /**
   * Class Constructor.
   *
   * @param \Recurly_Subscription $subscription
   *   The subscription.
   * @param Symfony\Component\HttpFoundation\Request $current_request
   *   The current HTTP/S request.
   * @param Drupal\Core\Entity\EntityStorageInterface $node_storage
   *   Node storage.
   * @param Drupal\Core\Config\ImmutableConfig $recurly_config
   *   The Recurly configuration.
   * @param Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler.
   */
  public function __construct(
      \Recurly_Subscription $subscription,
      Request $current_request = NULL,
      EntityStorageInterface $node_storage = NULL,
      ImmutableConfig $recurly_config = NULL,
      ModuleHandlerInterface $module_handler = NULL
  ) {
    parent::__construct($current_request, $node_storage, $recurly_config, $module_handler);
    $this->subscription = $subscription;
  }

  /**
   * Gets the subscription URL from an ID.
   *
   * @return string
   *   The subscription URL.
   */
  public function getUrl() {
    $subdomain = $this->recurlyConfig->get('recurly_subdomain');
    return 'https://' . $subdomain . '.recurly.com/subscriptions/' . $this->getUuid();
  }

  /**
   * Creates a site record.
   *
   * @param int $user_id
   *   The ID of the user whose site is being created.
   */
  public function createSite($user_id) {
    $subscription_id = $this->getUuid();

    $site = Node::create([
      'type' => 'recurly_aegir_site',
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
   * @see hook_recurly_aegir_quota_info()
   */
  public function getAddons() {
    $add_ons = [];

    foreach ($this->subscription->subscription_add_ons as $addon_object) {
      $add_ons[] = [
        'code' => $addon_object->add_on_code,
        'quantity' => $addon_object->quantity,
      ];
    }

    return $add_ons;
  }

  /**
   * Fetches the list of site IDs associated with this subscription.
   *
   * @param int $user_id
   *   The local Drupal user ID.
   *
   * @return array
   *   The list of site IDs.
   */
  public function getSiteIds($user_id) {
    return $this->nodeStorage->getQuery()
      ->condition('type', 'recurly_aegir_site')
      ->condition('uid', $user_id)
      ->condition('field_site_subscription_url.title', $this->getUuid())
      ->execute();
  }

  /**
   * Factory method.
   *
   * @param string $subscription_id
   *   The subscription ID.
   */
  public static function get($subscription_id) {
    if (empty($subscription_id)) {
      return NULL;
    }

    try {
      $subscription = \Recurly_Subscription::get($subscription_id);
    }
    catch (Exception $e) {
      return NULL;
    }

    return is_object($subscription) ? static::create(\Drupal::getContainer(), $subscription) : NULL;
  }

  /**
   * Fetches the state of the subscription.
   */
  public function getState() {
    return $this->subscription->state;
  }

  /**
   * Fetches the UUID.
   *
   * @return string||null
   *   The UUID or NULL if there isn't one.
   */
  public function getUuid() {
    return $this->subscription->uuid;
  }

  /**
   * Fetches the local user ID.
   *
   * @param string $account_code
   *   The account code.
   *
   * @return int
   *   The user ID.
   */
  public static function getLocalUserId($account_code) {
    return recurly_account_load([
      'account_code' => $account_code,
    ], TRUE)->entity_id;
  }

  /**
   * Fetches a site corresponding to the subscription if it's active.
   *
   * @param string $account_code
   *   The remote account code.
   *
   * @return Drupal\node\Entity\Node||null
   *   The site node or NULL if there isn't a site with an active subscription.
   */
  public function getSiteIfSubscriptionIsActive($account_code) {
    if (($site = $this->getSite($account_code)) &&
        (new SiteWrapper($site))->subscriptionIsActive()) {
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
   * @return Drupal\node\Entity\Node||null
   *   The site node or NULL if there isn't an associated site.
   */
  public function getSite($account_code) {
    $site_ids = $this->getSiteIds($this->getLocalUserId($account_code));
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
   */
  public function getPlanCode() {
    return $this->subscription->plan->plan_code;
  }

  /**
   * Determines if the subscription has expired or not.
   *
   * @return bool
   *   TRUE if the subscription has expired; FALSE otherwise.
   */
  public function isExpired() {
    return ($this->getState() == 'expired') ? TRUE : FALSE;
  }

  /**
   * Determines if the subscription is active or not.
   *
   * @return bool
   *   TRUE if the subscription is active; FALSE otherwise.
   */
  public function isActive() {
    return ($this->getState() == 'active') ? TRUE : FALSE;
  }

}
