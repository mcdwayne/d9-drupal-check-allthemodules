<?php

namespace Drupal\commerce_vl;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\facets\Exception\Exception;
use Drupal\user\UserInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Class ViralLoopsIntegrator.
 */
class ViralLoopsIntegrator implements ViralLoopsIntegratorInterface {

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Routing\AdminContext definition.
   *
   * @var \Drupal\Core\Routing\AdminContext
   */
  protected $routerAdminContext;

  /**
   * Drupal\Core\Config\ConfigFactoryInterface definition.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $client;

  /**
   * Logger.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The key/value manager service.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueFactoryInterface
   */
  protected $keyValue;

  /**
   * The coupon storage.
   *
   * @var \Drupal\commerce_promotion\CouponStorageInterface
   */
  protected $couponStorage;

  /**
   * The promotion storage.
   *
   * @var \Drupal\commerce_promotion\PromotionStorageInterface
   */
  protected $promotionStorage;

  /**
   * User storage.
   *
   * @var \Drupal\user\UserStorageInterface
   */
  protected $userStorage;

  /**
   * The cron queue for a Viral Loops coupon redemption.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $couponRedeemCronQueue;

  /**
   * The cron queue for processing completed order data.
   *
   * @var \Drupal\Core\Queue\QueueInterface
   */
  protected $processCompletedOrderCronQueue;

  /**
   * Constructs a new ViralLoopsIntegrator object.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   The current user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type repository.
   * @param \Drupal\Core\Routing\AdminContext $admin_context
   *   The route admin context service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory load.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \GuzzleHttp\Client $client
   *   The HTTP client.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Logger channel factory.
   * @param \Drupal\Core\KeyValueStore\KeyValueFactoryInterface $key_value
   *   The key/value manager service.
   * @param \Drupal\Core\Queue\QueueFactory $queue_factory
   *   The queue factory.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function __construct(
    AccountProxyInterface $current_user,
    EntityTypeManagerInterface $entity_type_manager,
    AdminContext $admin_context,
    ConfigFactoryInterface $config_factory,
    RequestStack $request_stack,
    Client $client,
    LoggerChannelFactoryInterface $logger_factory,
    KeyValueFactoryInterface $key_value,
    QueueFactory $queue_factory
  ) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
    $this->routerAdminContext = $admin_context;
    $this->configFactory = $config_factory;
    $this->request = $request_stack->getCurrentRequest();
    $this->client = $client;
    $this->logger = $logger_factory->get('commerce_vl');
    $this->keyValue = $key_value;
    $this->couponStorage = $this->entityTypeManager->getStorage('commerce_promotion_coupon');
    $this->promotionStorage = $this->entityTypeManager->getStorage('commerce_promotion');
    $this->userStorage = $this->entityTypeManager->getStorage('user');

    $this->couponRedeemCronQueue = $queue_factory->get(static::COUPON_REDEEM_CRON_QUEUE, TRUE);
    $this->processCompletedOrderCronQueue = $queue_factory->get(static::COMPLETED_ORDER_CRON_QUEUE, TRUE);
  }

  /**
   * Collect needed data for the Viral Loops widget script.
   *
   * @return array
   *   An array with data for the Viral Loops widget snippet.
   *   See viral-loops.js
   */
  public function getWidgetData() {
    $config = $this->configFactory->get('commerce_vl.viralloops');
    if ($config->get('vl_campaign_id')) {
      return [
        'campaign_id' => $config->get('vl_campaign_id'),
        'timeout_delay' => $config->get('vl_delay'),
      ];
    }
    else {
      return [];
    }
  }

  /**
   * Prepares user data for the Viral Loops script.
   *
   * @return array
   *   An array with user data which is needed on viral-loops.js.
   */
  public function getClientIdentifyUserData() {
    if ($this->userAccessWidget()) {
      /** @var \Drupal\user\UserInterface $account */
      $account = $this->userStorage->load($this->currentUser->id());

      return [
        'firstname' => $account->field_first_name->value ?? $account->getDisplayName(),
        'lastname' => $account->field_last_name->value ?? '',
        'email' => $account->getEmail(),
        'createdAt' => (int) $account->getCreatedTime(),
      ];
    }
    return [];
  }

  /**
   * Check if there is a marker to logout a user from Viral Loops.
   *
   * @return bool
   *   TRUE - user needs to be logged out.
   */
  public function needLogout() {
    if ($this->currentUser->isAnonymous()) {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Check if user is allowed to see VL widget.
   *
   * @param \Drupal\Core\Session\AccountInterface|null $account
   *   User account.
   *
   * @return bool
   *   TRUE - user has rights to see and use Viral Loops widget.
   */
  protected function userAccessWidget($account = NULL) {
    if (!$account instanceof AccountInterface) {
      $account = $this->currentUser;
    }
    return $account->hasPermission('use viral loops widget');
  }

  /**
   * {@inheritdoc}
   */
  public function sendServerIdentifyUserRequest(UserInterface $account) {
    $vl_api_token = $this->getApiToken();
    if (!$vl_api_token || !$this->userAccessWidget($account)) {
      return FALSE;
    }

    // The data to send to the Viral Loops API.
    $data = [
      'apiToken' => $vl_api_token,
      'params' => [
        'event' => 'action',
        'user' => [
          'firstname' => $account->field_first_name->value ?? $account->getDisplayName(),
          'lastname' => $account->field_last_name->value ?? '',
          'email' => $account->getEmail(),
          'createdAt' => (int) $account->getCreatedTime(),
        ],
      ],
    ];

    $session = $this->request->getSession();
    if ($session->get('vl_referral_code')) {
      $data['params']['referrer'] = ['referralCode' => $session->get('vl_referral_code')];
    }

    try {
      $request = $this->client->post(self::VL_API_EVENTS_URL, ['json' => $data]);
      $response = Json::decode($request->getBody());

      // If there is a code and user do not have one stored then proceed.
      if (!empty($response['referralCode'])
        && $account->hasField('vl_referral_code')
        && $account->vl_referral_code->isEmpty()
      ) {
        $account->set('vl_referral_code', $response['referralCode']);
        try {
          $account->save();
        }
        catch (EntityStorageException $e) {
          $this->logger->warning($e->getMessage());
        }
      }

      // Creates coupons.
      $this->processViralLoopsRewards($response);

      return TRUE;
    }
    catch (ClientException $exception) {
      $this->logger->warning($exception->getMessage());
      return FALSE;
    }
  }

  /**
   * Return a token for the Viral Loops API.
   *
   * @return array|mixed|null
   *   Viral Loops API token.
   */
  protected function getApiToken() {
    $config = $this->configFactory->get('commerce_vl.viralloops');
    return $config->get('vl_api_token');
  }

  /**
   * Creates Viral Loops and Drupal coupons.
   *
   * @param array $response
   *   Viral Loops response.
   * @param string $currency
   *   Currency code.
   */
  protected function processViralLoopsRewards(array $response, $currency = 'USD') {
    if ($this->checkViralLoopsResponseIssues($response)) {
      return;
    }

    foreach ($response['rewards'] as $reward) {
      $reward['coupon']['currency'] = $currency;
      $this->createDrupalCoupon($reward);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function handleOrderCompletion(OrderInterface $order) {
    $order_owner_referral_code = $this->getUserReferralCode($order->getCustomer());
    if (!$this->getApiToken() || !$order_owner_referral_code) {
      return;
    }

    // Redeems coupons.
    foreach ($this->getOrderViralLoopsCoupons($order) as $coupon) {
      // Inform viral loops that it has been redeemed.
      $this->couponRedeemCronQueue->createQueue();
      $this->couponRedeemCronQueue->createItem(['args' => [$coupon->vl_reward_id->value]]);
    }

    // The data to send to the Viral Loops API.
    $data = [
      'apiToken' => $this->getApiToken(),
      'params' => [
        'event' => 'order',
        'user' => ['referralCode' => $order_owner_referral_code],
        'amount' => $this->getOrderItemsPriceAmount($order),
      ],
    ];
    $this->processCompletedOrderCronQueue->createQueue();
    $this->processCompletedOrderCronQueue->createItem([
      'args' => [
        $data,
        $order->getTotalPrice()->getCurrencyCode(),
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function processCompletedOrderData(array $request_data, $currency_code) {
    try {
      $request = $this->client->post(self::VL_API_EVENTS_URL, ['json' => $request_data]);
      $response = Json::decode($request->getBody());

      // Creates coupons.
      $this->processViralLoopsRewards($response, $currency_code);
    }
    catch (\Exception $exception) {
      $this->logger->warning($exception->getMessage());
    }
  }

  /**
   * Return an adjusted prices sum of order items.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   Commerce Order entity.
   *
   * @return float
   *   A sum of adjusted price of order items.
   */
  protected function getOrderItemsPriceAmount(OrderInterface $order) {
    $items_price = new Price(0, $order->getTotalPrice()->getCurrencyCode());
    foreach ($order->getItems() as $order_item) {
      $items_price = $items_price->add($order_item->getAdjustedTotalPrice());
    }
    return (float) $items_price->getNumber();
  }

  /**
   * Return user referral code.
   *
   * @param mixed|\Drupal\user\Entity\User $account
   *   User entity.
   *
   * @return null|string
   *   Viral Loops referral code.
   */
  protected function getUserReferralCode($account) {
    $vl_referral_code = NULL;
    if ($account instanceof UserInterface
      && $account->hasField('vl_referral_code')
      && !$account->vl_referral_code->isEmpty()
    ) {
      $vl_referral_code = $account->vl_referral_code->value;
    }
    return $vl_referral_code;
  }

  /**
   * Check if there is any issue with the Viral Loops response.
   *
   * @param array $response
   *   Response from Viral Loops API.
   *
   * @return bool
   *   Return TRUE if there is any issue with VL response.
   */
  protected function checkViralLoopsResponseIssues(array $response) {
    $log_message = '';
    if (isset($response['error'])) {
      $log_message = $response['error']['message'];
    }

    // If the previous call was successful, there will be an eventId in the
    // response. If there is no eventId there will be a status like
    // “user already rewarded” or “user has no referrer”,
    // or “user has already made an action”.
    if (!empty($response['status'])) {
      switch ($response['status']) {
        case 'User has no referrer':
        case 'User has been already rewarded':
        case 'User has already made an action':
          $log_message = $response['status'];
          break;
      }
    }

    if (empty($response['rewards'])) {
      $log_message = 'Empty rewards.';
    }

    if ($log_message) {
      $this->logger->notice($log_message . 'User: @uid', ['@uid' => $this->currentUser->id()]);
    }

    return $log_message;
  }

  /**
   * Create a commerce coupon to use in checkout.
   *
   * @param array $reward
   *   The reward we got from the response of the coupon creation POST request.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The commerce_coupon object that we created.
   */
  protected function createDrupalCoupon(array $reward) {
    // If there is a coupon code already do nothing.
    if ($this->checkDrupalCouponExistence($reward)) {
      return NULL;
    }

    // Load user by mail so we can get the user id. We will use it later on.
    $user = user_load_by_mail($reward['user']['email']);

    // If we don't have a user we should do nothing. This is not suppose to
    // happen and this chuck of code is here as a fail-safe.
    if (empty($user)) {
      return NULL;
    }

    $viral_loops_promotion = $this->ensureViralLoopsPromotion();
    $coupon_data = [
      'promotion_id' => $viral_loops_promotion->id(),
      'usage_limit' => 1,
      'code' => $reward['coupon']['name'],
      'vl_reward_id' => $reward['id'],
      'vl_marker' => 1,
      'vl_type' => $reward['coupon']['type'],
      'vl_value' => $reward['coupon']['value'],
    ];

    $coupon = $this->couponStorage->create($coupon_data);
    try {
      $coupon->save();
      return $coupon;
    }
    catch (EntityStorageException $e) {
      $this->logger->warning($e->getMessage());
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function redeemViralLoopsCoupon($reward_id) {
    $vl_api_token = $this->getApiToken();
    if (!$vl_api_token) {
      return FALSE;
    }

    // The data to send to the Viral Loops API.
    $data = [
      'apiToken' => $vl_api_token,
      'rewardId' => $reward_id,
    ];

    try {
      $request = $this->client->post(self::VL_API_REDEEM_URL, ['json' => $data]);
      return Json::decode($request->getBody());
    }
    catch (\Exception $exception) {
      $this->logger->warning($exception->getMessage());
      return FALSE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getViralLoopsPromotion($return_id = FALSE) {
    $vl_promotion_id = $this->keyValue->get('commerce_vl')
      ->get('vl_promotion_id', NULL);

    if ($return_id) {
      return $vl_promotion_id;
    }
    elseif ($vl_promotion_id) {
      return $this->promotionStorage->load($vl_promotion_id);
    }
    return NULL;
  }

  /**
   * Check if coupon is already stored in the Drupal DB.
   *
   * @param array $reward
   *   The reward we got from the response of the coupon creation POST request.
   *
   * @return bool
   *   TRUE - coupon is already stored.
   */
  protected function checkDrupalCouponExistence(array $reward) {
    $existing_coupon = $this->couponStorage->loadByProperties(['code' => $reward['coupon']['name']]);
    if ($existing_coupon) {
      return TRUE;
    }
    else {
      $existing_coupon = $this->couponStorage->loadByProperties(['vl_reward_id' => $reward['id']]);
      if ($existing_coupon) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Create (if it is needed) and load Viral Loops promotion.
   *
   * @return \Drupal\commerce_promotion\Entity\Promotion|null
   *   Default Viral Loops promotion entity.
   */
  protected function ensureViralLoopsPromotion() {
    $promotion = $this->getViralLoopsPromotion();
    if (!$promotion) {
      $promotion = $this->promotionStorage->create([
        'compatibility' => 'any',
        'description' => 'Default promotion for Viral Loops coupons.',
        'name' => 'Viral Loops',
        'stores' => 1,
        'status' => TRUE,
        'order_types' => ['default'],
        'offer' => [
          'target_plugin_id' => 'viral_loops_offer',
          'target_plugin_configuration' => [],
        ],
      ]);
      try {
        $promotion->save();
      }
      catch (EntityStorageException $e) {
        $this->logger->warning($e->getMessage());
        return NULL;
      }
      $this->keyValue->get('commerce_vl')->set('vl_promotion_id', $promotion->id());
    }
    return $promotion;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderViralLoopsCoupon(OrderInterface $order) {
    /** @var \Drupal\commerce_promotion\Entity\CouponInterface[] $coupons */
    $coupons = $order->get('coupons')->referencedEntities();
    foreach ($coupons as $coupon) {
      if ($coupon->hasField('vl_marker') && $coupon->vl_marker->value) {
        return $coupon;
      }
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getOrderViralLoopsCoupons(OrderInterface $order) {
    $vl_coupons = [];
    $coupons = $order->get('coupons')->referencedEntities();
    foreach ($coupons as $coupon) {
      if ($coupon->hasField('vl_marker') && $coupon->vl_marker->value) {
        $vl_coupons[] = $coupon;
      }
    }
    return $vl_coupons;
  }

}
