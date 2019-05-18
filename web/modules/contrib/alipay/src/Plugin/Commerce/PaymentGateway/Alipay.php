<?php

namespace Drupal\alipay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderItem;
use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\alipay\AlipayGatewayInterface;
use Omnipay\Alipay\AopAppGateway;
use Omnipay\Alipay\Responses\AopCompletePurchaseResponse;
use Omnipay\Alipay\Responses\AopTradeAppPayResponse;
use Omnipay\Omnipay;
use Symfony\Component\HttpFoundation\Request;

/**
 * 支付支付
 *
 * @CommercePaymentGateway(
 *   id = "alipay",
 *   label = "Alipay",
 *   display_label = "Alipay"
 * )
 */
class Alipay extends OffsitePaymentGatewayBase implements SupportsRefundsInterface, AlipayGatewayInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['client_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('调用此支付网关的应用类型'),
      '#options' => [
        self::CLIENT_TYPE_NATIVE_APP => '原生移动应用'
      ],
      '#default_value' => $this->configuration['client_type'],
      '#required' => TRUE
    ];

    $form['app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('App ID'),
      '#description' => $this->t('绑定支付的APP ID'),
      '#default_value' => $this->configuration['app_id'],
      '#required' => TRUE,
    ];

    $form['app_private_key_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Private key path'),
      '#description' => $this->t('The app private key'),
      '#default_value' => $this->configuration['app_private_key_path']
    ];

    $form['alipay_public_key_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Public key path'),
      '#description' => $this->t('The alipay public key'),
      '#default_value' => $this->configuration['alipay_public_key_path']
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['client_type'] = $values['client_type'];
      $this->configuration['app_id'] = $values['app_id'];
      $this->configuration['app_private_key_path'] = $values['app_private_key_path'];
      $this->configuration['alipay_public_key_path'] = $values['alipay_public_key_path'];
    }
  }

  /**
   * @param $type
   * @return AopAppGateway
   */
  public function getOmniGateway($type) {
    /** @var AopAppGateway $gateway */
    $gateway = Omnipay::create('Alipay_AopApp');
    $gateway->setSignType('RSA2'); //RSA/RSA2

    $gateway->setAppId($this->getConfiguration()['app_id']);
    $gateway->setPrivateKey($this->getConfiguration()['app_private_key_path']);
    $gateway->setAlipayPublicKey($this->getConfiguration()['alipay_public_key_path']);

    global $base_url;
    $notify_url = $base_url . '/' . $this->getNotifyUrl()->getInternalPath();
    $gateway->setNotifyUrl($notify_url);

    return $gateway;
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {

  }

  /**
   * {@inheritdoc}
   * @throws \EasyWeChat\Core\Exceptions\FaultException
   */
  public function onNotify(Request $request) {
    \Drupal::logger('alipay')->notice('接收到来自支付宝的通知：' . print_r($_POST, TRUE));

    $client_type = $this->getConfiguration()['client_type'];
    $request = null;
    switch ($client_type) {
      case self::CLIENT_TYPE_NATIVE_APP:
        $request = $this->getOmniGateway('Alipay_AopApp')->completePurchase();
        break;

      default:
        throw new \Exception('未实现的客户端类型');
    }

    $request = $this->getOmniGateway()->completePurchase();
    $request->setParams($_POST);//Optional

    /** @var AopCompletePurchaseResponse $response */
    try {
      $response = $request->send();

      if($response->isPaid()){
        \Drupal::logger('alipay')->notice('通知验证成功。');

        // 处理订单状态
        // load the payment
        $order_id = null;
        $payment_id = null;
        $id_info = explode('-', $_POST['out_trade_no']);
        if ($id_info && count($id_info) > 2) {
          $order_id = $id_info[0];
          $payment_id = $id_info[1];
        } else {
          \Drupal::logger('alipay')->error('out_trade_no不是预期格式[' . $_POST['out_trade_no'] . ']: ' . print_r($_POST, TRUE));
          die('fail');
        }

        /** @var \Drupal\commerce_payment\Entity\Payment $payment_entity */
        $payment_entity = Payment::load($payment_id);
        $order = \Drupal\commerce_order\Entity\Order::load($order_id);
        if ($payment_entity && (int)$payment_entity->getOrderId() === (int)$order_id) {
          $payment_entity->setState('completed');
          $payment_entity->setRemoteId($_POST['trade_no']);
          $payment_entity->save();

          $transition = $order->getState()->getWorkflow()->getTransition('place');
          $order->getState()->applyTransition($transition);
          $order->save();
        } else {
          // Payment doesn't exist
          \Drupal::logger('alipay')->error('找不到订单[' . $order_id . ']的支付单[' . $payment_id . ']: ' . print_r($_POST, TRUE));
          die('fail');
        }

        die('success');
      }else{
        \Drupal::logger('alipay')->notice('通知验证失败。');
        die('fail');
      }
    } catch (\Exception $e) {
      \Drupal::logger('alipay')->notice('通知验证请求没有成功：' . $e->getMessage());
      die('fail');
    }
  }

  /**
   * @param \Drupal\commerce_order\Entity\Order $commerce_order
   * @return Payment
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function createPayment(\Drupal\commerce_order\Entity\Order $commerce_order) {
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    $payment = $payment_storage->create([
      'state' => 'new',
      'amount' => $commerce_order->getTotalPrice(),
      'payment_gateway' => $this->entityId,
      'order_id' => $commerce_order,
      'test' => $this->getMode() === 'test'
    ]);

    $payment->save();

    return $payment;
  }

  /**
   * @param Order $commerce_order
   * @return null
   * @throws \Exception
   */
  public function getClientLaunchConfig($commerce_order) {
    $config = null;
    $client_type = $this->getConfiguration()['client_type'];

    $request = null;
    switch ($client_type) {
      case self::CLIENT_TYPE_NATIVE_APP:
        $request = $this->getOmniGateway('Alipay_AopApp')->purchase();
        break;

      default:
        throw new \Exception('未实现的客户端类型');
    }

    $payment = $this->createPayment($commerce_order);

    $order_item_names = '';
    foreach ($commerce_order->getItems() as $order_item) {
      /** @var OrderItem $order_item */
      $order_item_names .= $order_item->getTitle() . ', ';
    }

    $request->setBizContent([
      'subject'      => mb_substr(\Drupal::config('system.site')->get('name') . $this->t(' Order: ') . $commerce_order->getOrderNumber(), 0, 256),
      'body' => mb_substr($order_item_names, 0, 128),
      'out_trade_no' => $commerce_order->id() . '-' . $payment->id() . '-' .date('YmdHis') . mt_rand(1000, 9999), // 商户网站唯一订单号
      'total_amount' => '0.01',
      'product_code' => 'QUICK_MSECURITY_PAY', // 销售产品码，商家和支付宝签约的产品码，为固定值QUICK_MSECURITY_PAY
    ]);

    /** @var AopTradeAppPayResponse $response */
    $response = $request->send();
    $orderString = $response->getOrderString();

    $config['order_string'] = $orderString;

    return $config;
  }
}
