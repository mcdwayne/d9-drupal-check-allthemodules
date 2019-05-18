<?php

namespace Drupal\commerce_wechat_pay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_payment\Entity\Payment;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\SupportsRefundsInterface;
use Drupal\commerce_payment\Exception\InvalidRequestException;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use EasyWeChat\Factory;


/**
 * Provides WeChat QR-Code Payment Mode 2 gateway.
 * @link https://pay.weixin.qq.com/wiki/doc/api/native.php?chapter=6_5
 *
 * @CommercePaymentGateway(
 *   id = "qrcode_payment_mode_2",
 *   label = "WeChat QR-code Payment Mode 2",
 *   display_label = "WeChat QR-code Payment Mode 2",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_wechat_pay\PluginForm\QRCodePaymentForm",
 *   },
 *   payment_type = "wechat_pay"
 * )
 */
class QRCodePaymentMode2 extends OffsitePaymentGatewayBase implements SupportsRefundsInterface{
  use StringTranslationTrait;

  const KEY_URI_PREFIX = 'private://commerce_wechat_pay/';

  /** @var  \EasyWeChat\Payment\payment $gateway_lib */
  protected $gateway_lib;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['appid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('公众账号ID'),
      '#description' => $this->t('绑定支付的APPID（开户邮件中可查看）'),
      '#default_value' => $this->configuration['appid'],
      '#required' => TRUE,
    ];

    $form['mch_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('商户号'),
      '#description' => $this->t('开户邮件中可查看'),
      '#default_value' => $this->configuration['mch_id'],
      '#required' => TRUE,
    ];

    $form['key'] = [
      '#type' => 'textfield',
      '#title' => $this->t('商户支付密钥'),
      '#description' => $this->t('参考开户邮件设置（必须配置，登录商户平台自行设置）, 设置地址：https://pay.weixin.qq.com/index.php/account/api_cert'),
      '#default_value' => $this->configuration['key'],
      '#required' => TRUE,
    ];

    $form['sub_appid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('子商户公众账号ID'),
      '#description' => $this->t('绑定支付的APPID（开户邮件中可查看）'),
      '#default_value' => $this->configuration['sub_appid']
    ];

    $form['sub_mch_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('子商户号'),
      '#description' => $this->t('开户邮件中可查看'),
      '#default_value' => $this->configuration['sub_mch_id']
    ];

    $form['certpem'] = [
      '#type' => 'textarea',
      '#title' => $this->t('证书文件内容'),
      '#description' => $this->t('Please open your apiclient_cert.pem file, and copy/paste the content to this text area.'),
      '#default_value' => $this->configuration['certpem']
    ];

    $form['keypem'] = [
      '#type' => 'textarea',
      '#title' => $this->t('证书文件密钥'),
      '#description' => $this->t('Please open your apiclient_key.pem file, and copy/paste the content to this text area.'),
      '#default_value' => $this->configuration['keypem']
    ];

    $form['certpem_uri'] = [
      '#type' => 'hidden',
      '#default_value' => $this->configuration['certpem_uri']
    ];

    $form['keypem_uri'] = [
      '#type' => 'hidden',
      '#default_value' => $this->configuration['keypem_uri']
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {

    $values = $form_state->getValue($form['#parents']);
    if (!empty($values['certpem'])) {
      // Check the file directory for storing cert/key files
      $path = self::KEY_URI_PREFIX;
      $dir = file_prepare_directory($path, FILE_CREATE_DIRECTORY);
      if (!$dir) {
        $form_state->setError($form['certpem'], $this->t('Commerce WeChat Pay cannot find your private file system. Please make sure your site has private file system configured!'));
        return;
      }

      $new_uri = $values['appid'] . md5($values['certpem']);

      if (empty($this->configuration['certpem_uri'])){
      } else {
        file_unmanaged_delete(self::KEY_URI_PREFIX . $this->configuration['certpem_uri']);
      }
      // We regenerate pem file in case the files were missing during server migration
      $updated = file_unmanaged_save_data($values['certpem'], self::KEY_URI_PREFIX . $new_uri);
      if ($updated) {
        $values['certpem_uri'] = $new_uri;
      } else {
        $form_state->setError($form['certpem'], $this->t('Commerce WeChat Pay cannot save your "certpem" into a file. Please make sure your site has private file system configured!'));
      }
    }

    if (!empty($values['keypem'])) {
      // Check the file directory for storing cert/key files
      $path = self::KEY_URI_PREFIX;
      $dir = file_prepare_directory($path, FILE_CREATE_DIRECTORY);
      if (!$dir) {
        $form_state->setError($form['keypem'], $this->t('Commerce WeChat Pay cannot find your private file system. Please make sure your site has private file system configured!'));
        return;
      }

      $new_uri = $values['appid'] . md5($values['keypem']);

      if (empty($this->configuration['keypem_uri'])) {
      } else {
        file_unmanaged_delete(self::KEY_URI_PREFIX . $this->configuration['keypem_uri']);
      }
      // We regenerate pem file in case the files were missing during server migration
      $updated = file_unmanaged_save_data($values['keypem'], self::KEY_URI_PREFIX . $new_uri);
      if ($updated) {
        $values['keypem_uri'] = $new_uri;
      } else {
        $form_state->setError($form['keypem'], $this->t('Commerce WeChat Pay cannot save your "keypem" into a file. Please make sure your site has private file system configured!'));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    if (!$form_state->getErrors()) {
      $values = $form_state->getValue($form['#parents']);
      $this->configuration['appid'] = $values['appid'];
      $this->configuration['mch_id'] = $values['mch_id'];
      $this->configuration['key'] = $values['key'];
      $this->configuration['sub_appid'] = $values['sub_appid'];
      $this->configuration['sub_mch_id'] = $values['sub_mch_id'];
      $this->configuration['certpem'] = $values['certpem'];
      $this->configuration['keypem'] = $values['keypem'];
      $this->configuration['certpem_uri'] = $values['appid'] . md5($values['certpem']);
      $this->configuration['keypem_uri'] = $values['appid'] . md5($values['keypem']);
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

    /** @var \EasyWeChat\Payment\API $gateway; */
    $gateway = $this->gateway_lib;

    if (!$gateway['cert_path'] || !$gateway['key_path']) {
      throw new \InvalidArgumentException($this->t('Could not load the apiclient_cert.pem or apiclient_key.pem files, which are required for WeChat Refund. Did you configure them?'));
    }

    $app = Factory::payment($gateway);
    $result = $app->refund->byOutTradeNumber($payment->getOrderId(), $payment->getOrderId() . date("zHis"),
      bcmul($payment->getAmount()->getNumber(), '100', 0), bcmul($amount->getNumber(), '100', 0));

    if ($result['return_code'] != 'SUCCESS' || $result['result_code'] != 'SUCCESS'){
       \Drupal::logger('commerce_wechat_pay')->error(print_r($result, TRUE));
      // For any reason, we cannot get a preorder made by WeChat service
      if($result['return_code'] == 'FAIL'){
        throw new InvalidRequestException($this->t('WeChat Service cannot approve this request: ') . $result['return_msg']);
      } else {
        throw new InvalidRequestException($this->t('WeChat Service cannot approve this request: ') . $result['err_code_des']);
      }
    }

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
  }

  /**
   * {@inheritdoc}
   */
  public function onNotify(Request $request) {

    if (!$this->gateway_lib) {
      $this->loadGatewayConfig();
    }

    /** @var \EasyWeChat\Payment\API $gateway; */
    $gateway = $this->gateway_lib;

    $app = Factory::payment($gateway);
    $response = $app->handlePaidNotify(function($message, $fail) {
      $result = $message;

      if ($this->getMode()) {
        \Drupal::logger('commerce_wechat_pay')->notice(print_r($result, TRUE));
      }

      // load the payment
      /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
      $query= \Drupal::entityQuery('commerce_payment')
        ->condition('order_id', $result['out_trade_no'])
        ->addTag('commerce_wechat_pay:check_payment');
      $payment_id = $query->execute();
      /** @var \Drupal\commerce_payment\Entity\Payment $payment_entity */
      $payment_entity = Payment::load(array_values($payment_id)[0]);

      if (!$payment_entity || $payment_entity->getState() == 'completed') {
        return true;
      }

      if ($result['return_code'] === 'SUCCESS') { // return_code 表示通信状态，不代表支付状态

        if ($payment_id) {
          if ($payment_entity) {
            // set openid and sub_openid in payment_entity
            if ($payment_entity->get('wechat_openid')->getValue() == NULL) {
              $payment_entity->set('wechat_openid', $result['openid']);
            }
            if ($payment_entity->get('wechat_sub_openid')->getValue() == NULL) {
              $sub_openid = isset($result['sub_openid']) ? $result['sub_openid'] : NULL;
              if($sub_openid){
                $payment_entity->set('wechat_sub_openid', $sub_openid );
              }
            }
            // 用户是否支付成功
            if ($result['result_code'] === 'SUCCESS') {
              $payment_entity->setState('completed');
              $payment_entity->setRemoteId($result['transaction_id']);
            } else { // 用户支付失败
              $payment_entity->setState('paid_fail');
            }

            $payment_entity->save();
          } else {
            // Payment doesn't exist
            \Drupal::logger('commerce_wechat_pay')->error(print_r($result, TRUE));
          }
        }

      } else { //通信失败
        \Drupal::logger('commerce_wechat_pay')->error(print_r($result, TRUE));
        return $fail('Communication failed, please notify later!');
      }

      return TRUE; // Respond WeChat request that we have finished processing this notification
    });

    return $response;
  }

  /**
   * Create a Commerce Payment from a WeChat request successful result
   * @param  array $result
   * @param  string $state
   * @param null $order_id
   * @param  string $remote_state
   * @param \Drupal\commerce_price\Price|null $price
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function createPayment(array $result, $state, $order_id = NULL, $remote_state = NULL, Price $price = NULL) {
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');

    $request_time = \Drupal::time()->getRequestTime();

    if (array_key_exists('transaction_id', $result)) {
      $remote_id = $result['transaction_id'];
    } elseif (array_key_exists('prepay_id', $result)) {
      $remote_id = $result['prepay_id'];
    } else {
      $remote_id = NULL; // There is no $remote_id when USERPAYING
    }

    $payment = $payment_storage->create([
      'state' => $state,
      'amount' => $price? $price : new Price(strval($result['total_fee'] / 100), $result['fee_type']),
      'payment_gateway' => $this->entityId,
      'order_id' => $order_id? $order_id : $result['out_trade_no'],
      'test' => $this->getMode() == 'test',
      'remote_id' => $remote_id,
      'remote_state' => $remote_state,
      'authorized' => array_key_exists('time_start', $result) ? strtotime($result['time_start']) : $request_time,
      'authorization_expires' => array_key_exists('time_expire', $result) ? strtotime($result['time_expire']) : strtotime('+2 hours', $request_time),
      'captured' => array_key_exists('time_end', $result) ? strtotime($result['time_end']) : NULL,
      'wechat_openid' => isset($result['openid']) ? $result['openid'] : NULL,
      'wechat_sub_openid' => isset($result['sub_openid']) ? $result['sub_openid'] : NULL
    ]);

    $payment->save();

    return $payment;
  }

  /**
   * Load configuration from parameters first, otherwise from system configuration. This method exists so other part of system can override the configurations.
   * One use case would be multi-stores, each store has its own payment gateway configuration saved on other entity.
   * @param null $appid
   * @param null $mch_id
   * @param null $key
   * @param null $cert_path
   * @param null $key_path
   * @param null $mode
   * @param null $sub_appid
   * @param null $sub_mch_id
   */
  public function loadGatewayConfig($appid = NULL, $mch_id = NULL, $key = NULL, $cert_path = NULL, $key_path = NULL, $mode = NULL, $sub_appid = NULL, $sub_mch_id = NULL) {
    if (!$appid) {
      $appid = $this->getConfiguration()['appid'];
    }
    if (!$mch_id) {
      $mch_id = $this->getConfiguration()['mch_id'];
    }
    if (!$key) {
      $key = $this->getConfiguration()['key'];
    }
    if (!$sub_appid) {
      $sub_appid = $this->getConfiguration()['sub_appid'];
    }
    if (!$sub_mch_id) {
      $sub_mch_id = $this->getConfiguration()['sub_mch_id'];
    }
    if (!$cert_path) {
      $cert_path = drupal_realpath(self::KEY_URI_PREFIX . $this->getConfiguration()['certpem_uri']);
    }
    if (!$key_path) {
      $key_path = drupal_realpath(self::KEY_URI_PREFIX . $this->getConfiguration()['keypem_uri']);
    }
    if (!$mode) {
      $mode = $this->getMode();
    }

    $options = [
      // 必要配置
      'app_id' => $appid,
      'mch_id'        => $mch_id,
      'key'                => $key,
      // 如需使用敏感接口（如退款、发送红包等）需要配置 API 证书路径(登录商户平台下载 API 证书)
      'cert_path'          => $cert_path,
      'key_path'           => $key_path,
      // 'device_info'     => '013467007045764',
      'sub_appid'      => $sub_appid,
      'sub_mch_id' => $sub_mch_id,
    ];

    if ($mode == 'test') {
      $options['sandbox'] = TRUE;
    }

    $app = Factory::payment($options);
    $wechat_pay = $app->getConfig();

    $this->gateway_lib = $wechat_pay;
  }

  /**
   *
   * @param string $order_id order id
   * @param \Drupal\commerce_price\Price $total_amount
   * @param string $notify_url
   * @param string $device_info
   * @return \Drupal\commerce_payment\Entity\Payment
   */
  public function requestQRCode($order_id, Price $total_amount, $notify_url = NULL, $device_info = '') {
    if (!$this->gateway_lib) {
      $this->loadGatewayConfig();
    }

    // Check if the $order_id has already requested a QRCode, then would have created a payment already
    /** @var \Drupal\Core\Entity\Query\QueryInterface $query */
    $query= \Drupal::entityQuery('commerce_payment')
      ->condition('order_id', $order_id)
      ->addTag('commerce_wechat_pay:check_payment');
    $payment_id = $query->execute();
    if ($payment_id) {
      /** @var \Drupal\commerce_payment\Entity\Payment $payment_entity */
      $payment_entity = Payment::load(array_values($payment_id)[0]);
      return $payment_entity;
    }

    /** @var \EasyWeChat\Payment\API $gateway; */
    $gateway = $this->gateway_lib;
    /*
     * We can't use the below more elegant way to get notify_url, because of this major core bug
     * https://www.drupal.org/node/2744715
     * $gateway->setNotifyUrl($this->getNotifyUrl()->toString());
     */
    if (!$notify_url) {
      global $base_url;
      $notify_url = $base_url . '/payment/notify/' . $this->entityId;
    }

    $attributes = [
      'trade_type'   => 'NATIVE', // JSAPI，NATIVE，APP...
      'body'         => empty($device_info) ? \Drupal::config('system.site')->get('name') . $this->t(' Order: ') . $order_id
        : $device_info . $this->t(' Order: ') . $order_id,
//      'detail'       => 'order_items in certain format',
      'out_trade_no' => $order_id . '', // Make sure this id is a string
      'total_fee'    => $total_amount->getNumber() * 100, // WeChat Pay use Integer for its price
      'fee_type'     => $total_amount->getCurrencyCode(),
      'notify_url'   => $notify_url
    ];

    $gateway['device_info'] = $device_info;
    $app = Factory::payment($gateway);

    try {
      /** @var \EasyWeChat\Kernel\Support\Collection $response */
      $response = $app->order->unify($attributes);
      if ($response['return_code'] == 'SUCCESS' && $response['result_code'] == 'SUCCESS') {

        /** @var \Drupal\commerce_payment\Entity\Payment $payment_entity */
        $payment_entity = $this->createPayment($response, 'authorization', $order_id, $response['code_url'], $total_amount);

        return $payment_entity;

      } else {
        throw new BadRequestHttpException($response['err_code_des'] . ': '. $response['return_msg']);
      }

    } catch (\Exception $e) {
      // Request is not successful
      \Drupal::logger('commerce_wechat_pay')->error($e->getMessage());
      throw new BadRequestHttpException($e->getMessage());
    }
  }

  /**
   * @param $order_id
   * @param \Drupal\commerce_payment\Entity\Payment $payment
   * @return \Drupal\commerce_payment\Entity\Payment|\Drupal\Core\Entity\EntityInterface
   */
  public function checkRemoteState($order_id, Payment $payment=NULL) {
    if (!$this->gateway_lib) {
      $this->loadGatewayConfig();
    }

    /** @var \EasyWeChat\Payment\API $gateway; */
    $gateway = $this->gateway_lib;
    $app = Factory::payment($gateway);
    $response = $app->order->queryByOutTradeNumber($order_id.'');
    \Drupal::logger('commerce_wechat_pay')->notice(print_r($response, TRUE));

    if ($response['return_code'] == 'SUCCESS' && $response['result_code'] == 'SUCCESS') {
      try {
        if (!$payment) {
          $total_amount = new Price((string) bcdiv($response['total_fee'], 100, 2), $response['fee_type']);
          $payment = $this->createPayment($response, $response['trade_state'], $order_id, NULL, $total_amount);
        }
      } catch (\Exception $e) {
        // create payment failed
        \Drupal::logger('commerce_wechat_pay')->error($e->getMessage() . ' ' . $response['trade_state_desc']);
        throw new NotFoundHttpException($e->getMessage() . ' ' . $response['trade_state_desc']);
      }

      if ($payment->getRemoteState() !== $response['trade_state'] || ($response['trade_state'] === 'REFUND' && $payment->getState() !== 'refunded')) {
        $payment->setRemoteState($response['trade_state']);

        if ($response['trade_state'] === 'SUCCESS') {
          $payment->setRemoteId($response['transaction_id']);
          $payment->setState('completed');
          // set openid and sub_openid when the trade_state is 'SUCCESS'
          if ($payment->get('wechat_openid')->getValue() == NULL) {
            $payment->set('wechat_openid', $response['openid']);
          }
          if ($payment->get('wechat_sub_openid')->getValue() == NULL) {
            $sub_openid = isset($response['sub_openid']) ? $response['sub_openid'] : NULL;
            if($sub_openid){
              $payment->set('wechat_sub_openid', $sub_openid );
            }
          }
        }
        elseif ($response['trade_state'] === 'REFUND') {
          if ($payment->getRefundedAmount() !== $payment->getAmount()) {
            $refund_response = $this->refundQueryByOutTradeNumber($order_id);
            $fee_type = isset($refund_response['fee_type']) ? $refund_response['fee_type'] : 'CNY';
            $refund_amount = new Price((string) bcdiv($refund_response['refund_fee'], 100, 2), $fee_type);
            $payment->setRefundedAmount($refund_amount);
            if ($refund_response['refund_fee'] < $refund_response['total_fee']) {
              $payment->setState('partially_refunded');
            } else {
              $payment->setState('refunded');
            }
          } else {
            $payment->setState('refunded');
          }
        }
        elseif ($response['trade_state'] === 'NOTPAY' || $response['trade_state'] === 'USERPAYING') {
          $payment->setState('authorization');
        }
        elseif ($response['trade_state'] === 'CLOSED') {
          $payment->setState('authorization_expired');
        }
        elseif ($response['trade_state'] === 'REVOKED' || $response['trade_state'] === 'PAYERROR') {
          $payment->setState('authorization_voided');
        }

        $payment->save();
      }

      return $payment;

    } else {
      $err_code_des = isset($response['err_code_des']) ? $response['err_code_des'] : '';
      \Drupal::logger('commerce_wechat_pay')->warning('Order: ' . $order_id . ' ' . $err_code_des . ' ' . $response['return_msg']);
      throw new NotFoundHttpException('Order: ' . $order_id . ' ' . $err_code_des . ' ' . $response['return_msg']);
    }
  }

  /**
   * @param string $outTradeNumber
   *
   * @return array|\EasyWeChat\Kernel\Support\Collection|object|\Psr\Http\Message\ResponseInterface|string
   */
  public function refundQueryByOutTradeNumber(string $outTradeNumber){
    if (!$this->gateway_lib) {
      $this->loadGatewayConfig();
    }

    /** @var \EasyWeChat\Payment\API $gateway; */
    $gateway = $this->gateway_lib;

    $app = Factory::payment($gateway);
    try {
      $response = $app->refund->queryByOutTradeNumber($outTradeNumber);
      \Drupal::logger('commerce_wechat_pay')->notice(print_r($response, TRUE));
      if($response['return_code'] === 'SUCCESS' && $response['result_code'] === 'SUCCESS'){
        return $response;
      } else {
        if ($response['return_code'] === 'SUCCESS') {
          $error_msg = $response['err_code'] . ': ' . $response['err_code_des'] . ' '. $response['return_msg'];
        } else {
          $error_msg = $response['return_msg'];
        }
        throw new BadRequestHttpException($error_msg);
      }
    } catch (\Exception $e) {
      \Drupal::logger('commerce_wechat_pay')->error($e->getMessage());
      throw new NotFoundHttpException($e->getMessage());
    }
  }

}
