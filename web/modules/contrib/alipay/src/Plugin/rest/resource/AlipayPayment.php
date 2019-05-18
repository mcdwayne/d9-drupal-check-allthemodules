<?php

namespace Drupal\alipay\Plugin\rest\resource;

use Drupal\alipay\AlipayGatewayInterface;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "alipay_payment",
 *   label = @Translation("Alipay Payment"),
 *   uri_paths = {
 *     "create" = "/api/rest/alipay/payment"
 *   }
 * )
 */
class AlipayPayment extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new PaymentResource object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param array $serializer_formats
   *   The available serialization formats.
   * @param \Psr\Log\LoggerInterface $logger
   *   A logger instance.
   * @param \Drupal\Core\Session\AccountProxyInterface $current_user
   *   A current user instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    array $serializer_formats,
    LoggerInterface $logger,
    AccountProxyInterface $current_user) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer_formats, $logger);

    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->getParameter('serializer.formats'),
      $container->get('logger.factory')->get('commerce_checkout_api'),
      $container->get('current_user')
    );
  }

  /**
   * Responds to POST requests.
   *
   * @param OrderInterface $commerce_order
   * @param array $unserialized
   * @return \Drupal\rest\ModifiedResourceResponse
   *   The HTTP response object.
   *
   */
  public function post(array $data) {

    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    // 检查用户选择的支付方式，保存到订单
    if (!isset($data['gateway'])) {
      throw new BadRequestHttpException('没有指定网关');
    }
    $gateway_name = $data['gateway'];

    /** @var \Drupal\commerce_payment\PaymentGatewayStorageInterface $payment_gateway_storage */
    $payment_gateway_storage = \Drupal::service('entity_type.manager')->getStorage('commerce_payment_gateway');
    /** @var \Drupal\commerce_payment\Entity\PaymentGatewayInterface $payment_gateway */
    $payment_gateway = $payment_gateway_storage->load($gateway_name);
    $payment_gateway_plugin = $payment_gateway->getPlugin();
    if (!$payment_gateway) {
      throw new BadRequestHttpException('无效的支付网关');
    }
    if (!($payment_gateway_plugin instanceof AlipayGatewayInterface)) {
      throw new BadRequestHttpException('指定了非支付宝网关');
    }

    $commerce_order = Order::load($data['cart_id']);
    if ($commerce_order->get('state')->value !== 'draft') {
      throw new BadRequestHttpException('订单不符合支付条件，只能支付未下单(place)的订单。');
    }
    $commerce_order->set('payment_gateway', $payment_gateway);
    $commerce_order->save();

    $config_data = $payment_gateway_plugin->getClientLaunchConfig($commerce_order);

    return new ModifiedResourceResponse($config_data, 200);
  }

  /**
   * 暂时不做权限检查
   * @inheritdoc
   */
  public function permissions() {
    return [];
  }
}
