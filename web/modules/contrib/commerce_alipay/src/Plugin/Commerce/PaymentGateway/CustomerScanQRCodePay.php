<?php

namespace Drupal\commerce_alipay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Omnipay\Omnipay;

/**
 * Provides Alipay gateway for customer to scan QR-Code to pay.
 * @link https://doc.open.alipay.com/docs/doc.htm?treeId=194&articleId=105072&docType=1
 *
 * @CommercePaymentGateway(
 *   id = "alipay_customer_scan_qrcode_pay",
 *   label = "Alipay - Customer Scan QR-Code to Pay",
 *   display_label = "Alipay - Customer Scan QR-Code to Pay",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_alipay\PluginForm\QRCodePaymentForm",
 *   },
 *   payment_type = "alipay"
 * )
 */
class CustomerScanQRCodePay extends OffsitePaymentGatewayBase implements SupportsRefundsInterface{

  protected $gateway_lib;

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'app_id' => '',
        'public_key' => '',
        'private_key' => '',
        'app_auth_token' => '',
        'sys_service_provider_id' => ''
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['app_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('支付宝分配给开发者的应用ID'),
      '#default_value' => $this->configuration['app_id'],
      '#required' => TRUE,
    ];

    $form['private_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('开发者应用私钥'),
      '#description' => $this->t('应用私钥在创建订单时会使用到，需要它计算出签名供支付宝验证（应用公钥需要在支付宝开放平台中填写）'),
      '#default_value' => $this->configuration['private_key'],
      '#required' => TRUE,
    ];

    $form['public_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('支付宝公钥'),
      '#description' => $this->t('支付宝公钥在同步异步通知中会使用到，它能验证请求的签名是否是支付宝的私钥所签名。（支付宝公钥需要在支付宝开放平台中获取）'),
      '#default_value' => $this->configuration['public_key'],
      '#required' => TRUE,
    ];

    $form['app_auth_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('支付宝应用授权token'),
      '#default_value' => $this->configuration['app_auth_token'],
      '#required' => FALSE,
    ];

    $form['sys_service_provider_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('系统商编号'),
      '#description' => $this->t('该参数作为系统商返佣数据提取的依据，请填写系统商签约协议的PID'),
      '#default_value' => $this->configuration['sys_service_provider_id'],
      '#required' => FALSE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['app_id'] = $values['app_id'];
      $this->configuration['public_key'] = $values['public_key'];
      $this->configuration['private_key'] = $values['private_key'];
      $this->configuration['app_auth_token'] = $values['app_auth_token'];
      $this->configuration['sys_service_provider_id'] = $values['sys_service_provider_id'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refundPayment(PaymentInterface $payment, Price $amount = NULL) {
    $this->assertPaymentState($payment, ['completed', 'partially_refunded']);
    // If not specified, refund the entire amount.
    $amount = $amount ?: $payment->getAmount();
    // Validate the requested amount.
    $this->assertRefundAmount($payment, $amount);

    if (!$this->gateway_lib) {
      $this->loadGatewayConfig();
    }
    /** @var \Omnipay\Alipay\AopF2FGateway $gateway */
    $gateway = $this->gateway_lib;

    /** @var \Omnipay\Alipay\Requests\AopTradeRefundRequest $request */
    $request = $gateway->refund();

    $request->setBizContent([
      'out_trade_no' => strval($payment->getOrderId()),
      'trade_no' => $payment->getRemoteId(),
      'refund_amount' => (float) $amount->getNumber(),
      'out_request_no' => $payment->getOrderId() . date("zHis")
    ]);

    try {
      /** @var \Omnipay\Alipay\Responses\AopTradeRefundResponse $response */
      $response = $request->send();
      if($response->getAlipayResponse('code') == '10000'){
        // Refund is successful
        // Perform the refund request here, throw an exception if it fails.
        // See \Drupal\commerce_payment\Exception for the available exceptions.
        $remote_id = $payment->getRemoteId();
        $number = $amount->getNumber();

        $old_refunded_amount = $payment->getRefundedAmount();
        $new_refunded_amount = $old_refunded_amount->add($amount);
        if ($new_refunded_amount->lessThan($payment->getAmount())) {
          $payment->setState('partially_refunded');
        }
        else {
          $payment->setState('refunded');
        }

        $payment->setRefundedAmount($new_refunded_amount);
        $payment->save();

      } else {
        // Refund is not successful
        throw new InvalidRequestException(t('The refund request has failed: ') . $response->getAlipayResponse('sub_msg'));
      }
    } catch (\Exception $e) {
      // Refund is not successful
      \Drupal::logger('commerce_alipay')->error($e->getMessage());
      throw new InvalidRequestException(t('Alipay Service cannot approve this request: ') . $response->getAlipayResponse('sub_msg'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {

    if (!$this->gateway_lib) {
      $this->loadGatewayConfig();
    }
    /** @var \Omnipay\Alipay\AopF2FGateway $gateway */
    $gateway = $this->gateway_lib;

    /** @var \Omnipay\Alipay\Requests\AopCompletePurchaseRequest $virtual_request */
    $virtual_request = $gateway->completePurchase();
    $virtual_request->setParams($_POST); //Optional

    try {
      /** @var \Omnipay\Alipay\Responses\AopCompletePurchaseResponse $response */
      $response = $virtual_request->send();
      $data = $response->getData();

      if (array_key_exists('refund_fee', $data)) {
        die('success'); // Ignore refund notifcation
      } elseif ($data['trade_status'] == 'WAIT_BUYER_PAY') {
        die('success'); // Ignore waiting buyer to pay notifcation
      } elseif ($response->isPaid()) { // Payment is successful

        if ($this->getMode()) {
          \Drupal::logger('commerce_alipay')->notice(print_r($data, TRUE));
        }

        // load the payment
        /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
        $query= \Drupal::entityQuery('commerce_payment')
          ->condition('order_id', $data['out_trade_no'])
          ->addTag('commerce_alipay:check_payment');
        $payment_id = $query->execute();
        if ($payment_id) {
          /** @var \Drupal\commerce_payment\Entity\Payment $payment_entity */
          $payment_entity = Payment::load(array_values($payment_id)[0]);
          if ($payment_entity) {
            $payment_entity->state = 'completed';
            $payment_entity->setRemoteId($data['trade_no']);
            if (isset($data['buyer_id'])) {
              $payment_entity->set('alipay_buyer_user_id', $data['buyer_id']);
            }
            $payment_entity->save();
          } else {
            // Payment doesn't exist
            \Drupal::logger('commerce_alipay')->error(print_r($data, TRUE));
          }
        }

        die('success'); //The response should be 'success' only
      } else {
        // Payment is not successful
        \Drupal::logger('commerce_alipay')->error(print_r($data, TRUE));
        die('fail');
      }
    } catch (\Exception $e) {
      // Payment is not successful
      \Drupal::logger('commerce_alipay')->error($e->getMessage());
      \Drupal::logger('commerce_alipay')->error(file_get_contents('php://input'));
      die('fail');
    }
  }

  /**
   * Create a Commerce Payment from a WeChat request successful result
   * @param  array $result
   * @param  string $state
   * @param  string $order_id
   * @param  string $remote_state
   * @param \Drupal\commerce_price\Price|null $price
   * @return \Drupal\commerce_payment\Entity\PaymentInterface $payment
   */
  public function createPayment(array $result, $state = 'completed', $order_id = NULL, $remote_state = NULL, Price $price = NULL) {
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    if (isset($result['gmt_payment'])) {
      $captured = strtotime($result['gmt_payment']);
    } elseif (isset($result['send_pay_date'])) {
      $captured = strtotime($result['send_pay_date']);
    } else {
      $captured = NULL;
    }

    $payment = $payment_storage->create([
      'state' => $state,
      'amount' => $price? $price : new Price(strval($result['total_amount']), 'CNY'),
      'payment_gateway' => $this->entityId,
      'order_id' => $order_id? $order_id : $result['out_trade_no'],
      'test' => $this->getMode() == 'test',
      'remote_id' => array_key_exists('trade_no', $result) ? $result['trade_no'] : NULL,
      'remote_state' => $remote_state,
      'authorized' => \Drupal::time()->getRequestTime(),
      'captured' => $captured,
      'alipay_buyer_user_id' => isset($result['buyer_user_id']) ? $result['buyer_user_id'] : NULL
    ]);
    $payment->save();

    return $payment;
  }

  /**
   *
   * @param string $order_id order id
   * @param \Drupal\commerce_price\Price $total_amount
   * @param string $notify_url
   * @param string $store_id
   * @return \Drupal\commerce_payment\Entity\Payment
   */
  public function requestQRCode($order_id, Price $total_amount, $notify_url = NULL, $store_id = '') {
    if (!$this->gateway_lib) {
      $this->loadGatewayConfig();
    }

    // Check if the $order_id has already requested a QRCode, then would have created a payment already
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query= \Drupal::entityQuery('commerce_payment')
      ->condition('order_id', $order_id)
      ->addTag('commerce_alipay:check_payment');
    $payment_id = $query->execute();
    if ($payment_id) {
      /** @var \Drupal\commerce_payment\Entity\Payment $payment_entity */
      $payment_entity = Payment::load(array_values($payment_id)[0]);
      return $payment_entity;
    }

    /** @var \Omnipay\Alipay\AopF2FGateway $gateway */
    $gateway = $this->gateway_lib;
    $sys_service_provider_id = $gateway->getSysServiceProviderId();
    /*
     * We can't use the below more elegant way to get notify_url, because of this major core bug
     * https://www.drupal.org/node/2744715
     * $gateway->setNotifyUrl($this->getNotifyUrl()->toString());
     */
    if (!$notify_url) {
      global $base_url;
      $notify_url = $base_url . '/payment/notify/' . $this->entityId;
    }
    $gateway->setNotifyUrl($notify_url);

    $request = $gateway->purchase();
    $request->setBizContent([
      'subject'      => empty($store_id) ? \Drupal::config('system.site')->get('name') . t(' Order: ') . $order_id
        : $store_id . t(' Order: ') . $order_id,
      'out_trade_no' => $order_id,
      'store_id'     => $store_id,
      'total_amount' => (float) $total_amount->getNumber(),
      'extend_params' => ['sys_service_provider_id' => $sys_service_provider_id]
    ]);

    try {
      /** @var \Omnipay\Alipay\Responses\AopTradePreCreateResponse $response */
      $response = $request->send();

      if ($response->getAlipayResponse('code') == '10000') {  // Success
        // Create a payment entity
        $data = $response->getData();
        // Store QRCode in the remote state field
        /** @var \Drupal\commerce_payment\Entity\Payment $payment_entity */
        $payment_entity = $this->createPayment($data, 'authorization', $order_id, $response->getQrCode(), $total_amount);
        return $payment_entity;

      } else {
        throw new BadRequestHttpException($response->getAlipayResponse('sub_code') . ' ' .$response->getAlipayResponse('sub_msg'));
      }
    } catch (\Exception $e) {
      // Request is not successful
      \Drupal::logger('commerce_alipay')->error($e->getMessage());
      throw new BadRequestHttpException($e->getMessage());
    }
  }

  /**
   * @param string $order_id
   * @param string $auth_code
   * @param Price $total_amount
   * @param string $store_id
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function capture($order_id, $auth_code, Price $total_amount, $store_id = '') {
    if (!$this->gateway_lib) {
      $this->loadGatewayConfig();
    }
    /** @var \Omnipay\Alipay\AopF2FGateway $gateway */
    $gateway = $this->gateway_lib;
    $sys_service_provider_id = $gateway->getSysServiceProviderId();

    /** @var \Omnipay\Alipay\Requests\AopTradePayRequest $request */
    $request = $gateway->capture();
    $request->setBizContent([
      'out_trade_no' => (string) $order_id,
      'scene'        => 'bar_code',
      'store_id'     => $store_id,
      'auth_code'    => $auth_code,  //购买者手机上的付款二维码
      'subject'      => empty($store_id) ? \Drupal::config('system.site')->get('name') . t(' Order: ') . $order_id
        : $store_id . t(' Order: ') . $order_id,
      'total_amount' => (float) $total_amount->getNumber(),
      'extend_params' => ['sys_service_provider_id' => $sys_service_provider_id]
    ]);

    try {
      /** @var \Omnipay\Alipay\Responses\AopTradePayResponse $response */
      $response = $request->send();

      // TODO: need to handle AopTradeCancelResponse

      if ($response->isPaid()) {
        // Payment is successful
        $result = $response->getAlipayResponse();
        if ($result['code'] == '10000') {
          $payment_entity = $this->createPayment($result);
          return $payment_entity;
        }

      } else {
        // Payment is not successful
        \Drupal::logger('commerce_alipay')->error(print_r($response->getData(), TRUE));
        throw new BadRequestHttpException($response->getAlipayResponse('sub_code') . ' ' .$response->getAlipayResponse('sub_msg'));
      }
    } catch (\Exception $e) {
      // Payment is not successful
      \Drupal::logger('commerce_alipay')->error($e->getMessage());
      throw new BadRequestHttpException($e->getMessage());
    }
  }

  /**
   * Load configuration from parameters first, otherwise from system configuration. This method exists so other part of system can override the configurations.
   * One use case would be multi-stores, each store has its own payment gateway configuration saved on other entity.
   * @param null $app_id
   * @param null $private_key
   * @param null $public_key
   * @param null $mode
   * @return \Omnipay\Alipay\AopF2FGateway
   */
  public function loadGatewayConfig($app_id = NULL, $private_key = NULL, $public_key = NULL, $mode = NULL, $app_auth_token = NULL, $sys_service_provider_id = NULL) {
    if (!$app_id) {
      $app_id = $this->getConfiguration()['app_id'];
    }
    if (!$private_key) {
      $private_key = $this->getConfiguration()['private_key'];
    }
    if (!$public_key) {
      $public_key = $this->getConfiguration()['public_key'];
    }
    if (!$app_auth_token) {
      $app_auth_token = $this->getConfiguration()['app_auth_token'];
    }
    if (!$sys_service_provider_id) {
      $sys_service_provider_id = $this->getConfiguration()['sys_service_provider_id'];
    }
    if (!$mode) {
      $mode = $this->getMode();
    }

    /** @var \Omnipay\Alipay\AopF2FGateway $gateway */
    $gateway = Omnipay::create('Alipay_AopF2F');
    $gateway->setAppId($app_id);
    $gateway->setSignType('RSA2');
    $gateway->setPrivateKey($private_key);
    $gateway->setAlipayPublicKey($public_key);
    $gateway->setAppAuthToken($app_auth_token);
    $gateway->setSysServiceProviderId($sys_service_provider_id);
    if ($mode == 'test') {
      $gateway->sandbox(); // set to use sandbox endpoint
    }
    $this->gateway_lib = $gateway;
  }

  /**
   * @param $payment_id
   * @param $order_id
   *
   * @return \Drupal\commerce_payment\Entity\Payment|\Drupal\Core\Entity\EntityInterface|null|static
   */
  public function cancel($payment_id, $order_id){
    if (!$this->gateway_lib) {
      $this->loadGatewayConfig();
    }
    /** @var \Omnipay\Alipay\AopF2FGateway $gateway */
    $gateway = $this->gateway_lib;

    $payment_entity=payment::load($payment_id);
    if ($payment_entity) {
      /** @var \Omnipay\Alipay\Requests\AopTradePayRequest $request */
      $request = $gateway->cancel();
      $request->setBizContent([
        'out_trade_no' => $order_id,
      ]);

      try {
        $response = $request->send();
        $data = $response->getData();
        //cancel successful, then changed the order payment status
        if ($data['alipay_trade_cancel_response']['code'] == 10000) {
          \Drupal::logger('commerce_alipay')->info(print_r($data, TRUE));
          //have not scanned the QRCode then cancel the order, the returned message has no action and trade_no
          if (isset($data['alipay_trade_cancel_response']['action'])) {
            $state = $data['alipay_trade_cancel_response']['action'] == 'close' ? 'authorization_voided' : 'refunded';
          } else {
            $state = 'authorization_voided';
          }
          if (isset($data['alipay_trade_cancel_response']['trade_no'])) {
            $remote_id = $data['alipay_trade_cancel_response']['trade_no'];
            $payment_entity->setRemoteId($remote_id);
          }
          $payment_entity->setState($state);
          $payment_entity->save();
          return $payment_entity;
        } else {
          \Drupal::logger('commerce_alipay')->error(print_r($data, TRUE));
        }
      } catch (\Exception $e) {
        // Cancel is not successful
        \Drupal::logger('commerce_alipay')->error($e->getMessage());
        throw new BadRequestHttpException($e->getMessage());
      }
    } else {
      \Drupal::logger('commerce_alipay')->error('payment_id:'.$payment_id.'not exist!');
      return NULL;
    }
  }

}
