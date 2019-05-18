<?php

namespace Drupal\commerce_braintree_marketplace\Controller;

use Braintree\WebhookNotification;
use Drupal\commerce_braintree_marketplace\Event\DisbursementEvent;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\commerce_braintree_marketplace\Event\MerchantDisbursementExceptionEvent;
use Drupal\commerce_braintree_marketplace\Event\MerchantEvent;
use Drupal\commerce_braintree_marketplace\Event\BraintreeMarketplaceEvents;
use Drupal\profile\Entity\Profile;
use Drupal\profile\Entity\ProfileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class WebhookController extends ControllerBase {

  /**
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current request.
   *
   * @var null|\Symfony\Component\HttpFoundation\Request
   */
  protected $currentRequest;

  /**
   * The Entity Query factory service.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * The event dispatcher service.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Logger channel.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * The number of times to retry looking up a profile.
   *
   * @var int
   */
  const LOOKUP_RETRIES = 2;

  /**
   * The delay, in seconds, to wait until retrying a lookup.
   *
   * @var int
   */
  const LOOKUP_DELAY = 2;

  /**
   * {@inheritdoc}
   */
  public function __construct(EntityTypeManagerInterface $entity_manager, RequestStack $requestStack, QueryFactory $entityQuery, EventDispatcherInterface $eventDispatcher) {
    $this->entityTypeManager = $entity_manager;
    $this->currentRequest = $requestStack->getCurrentRequest();
    $this->entityQuery = $entityQuery;
    $this->eventDispatcher = $eventDispatcher;
    $this->logger = $this->getLogger('commerce_braintree_marketplace');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('entity.query'),
      $container->get('event_dispatcher')
    );
  }

  /**
   * Handle webhook callbacks from Braintree.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   */
  public function process() {
    $signature = $this->currentRequest->get('bt_signature');
    $payload = $this->currentRequest->get('bt_payload');
    /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('commerce_payment_gateway');
    // @todo - Perhaps have callback URL contain the plugin ID?
    if (!$gateway = $storage->loadByProperties(['plugin' => 'braintree_hostedfields_marketplace'])) {
      throw new BadRequestHttpException();
    }
    $config = reset($gateway)->getPlugin()->getConfiguration();
    // Webhook parse needs global configuration.
    \Braintree\Configuration::environment(($config['mode'] == 'test') ? 'sandbox' : 'production');
    \Braintree\Configuration::merchantId($config['merchant_id']);
    \Braintree\Configuration::publicKey($config['public_key']);
    \Braintree\Configuration::privateKey($config['private_key']);
    try {
      /** @var WebhookNotification $result */
      $result = WebhookNotification::parse($signature, $payload);
      if (!in_array($result->kind, [
        WebhookNotification::CHECK,
        WebhookNotification::SUB_MERCHANT_ACCOUNT_APPROVED,
        WebhookNotification::SUB_MERCHANT_ACCOUNT_DECLINED,
        WebhookNotification::DISBURSEMENT_EXCEPTION,
        WebhookNotification::DISBURSEMENT,
      ])) {
        $this->logger
          ->error('Could not parse webhook response from Braintree.');
        throw new \Exception();
      }
    }
    catch (\Throwable $e) {
      throw new BadRequestHttpException();
    }
    if ($result->kind == WebhookNotification::DISBURSEMENT_EXCEPTION) {
      $event = new MerchantDisbursementExceptionEvent($result);
      $this->eventDispatcher
        ->dispatch(BraintreeMarketplaceEvents::DISBURSEMENT_EXCEPTION, $event);
      $this->logger->notice('Processed disbursement exception from Braintree.');
    }
    else if ($result->kind == WebhookNotification::CHECK) {
      $this->logger->info('Processed CHECK event from Braintree.');
      return Response::create();
    }
    else if ($result->kind == WebhookNotification::DISBURSEMENT) {
      $event = new DisbursementEvent($result);
      $this->eventDispatcher
        ->dispatch(BraintreeMarketplaceEvents::DISBURSEMENT, $event);
      $this->logger->info('Processed disbursement notification from Braintree.');
    }
    else {
      // In testing, sometimes the webhook responds before we have a chance to
      // save the profile, resulting in a not-found condition.
      $search = [];
      for($i = 0; $i < self::LOOKUP_RETRIES; $i++) {
        $search = $this->entityQuery->get('profile')
          ->condition('braintree_id.remote_id', $result->merchantAccount->id)
          ->condition('braintree_id.provider', $result->merchantAccount->masterMerchantAccount->id)
          ->execute();
        if ($search) {
          break;
        }
        sleep(self::LOOKUP_DELAY);
      }
      if (!$search) {
        $this->logger->warning('Could not find profile ID @profile for Braintree submerchant webhook.', ['@profile' => $result->merchantAccount->id]);
        throw new BadRequestHttpException();
      }
      /** @var ProfileInterface $profile */
      $profile = Profile::load(reset($search));
      $event = new MerchantEvent($result, $profile);
      $this->eventDispatcher->dispatch(BraintreeMarketplaceEvents::PREFIX . $result->kind, $event);
      $this->logger->info('Processed submerchant webhook from Braintree.');
    }
    return Response::create();
  }
}
