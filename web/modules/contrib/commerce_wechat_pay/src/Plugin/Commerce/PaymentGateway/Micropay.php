<?php

namespace Drupal\commerce_wechat_pay\Plugin\Commerce\PaymentGateway;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\commerce_price\Price;
use EasyWeChat\Factory;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\commerce_payment\Entity\Payment;

/**
 * Provides WeChat Micropay gateway.
 * @link https://pay.weixin.qq.com/wiki/doc/api/micropay.php?chapter=5_1
 *
 * @CommercePaymentGateway(
 *   id = "micropay",
 *   label = "WeChat Micropay",
 *   display_label = "WeChat Micropay",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_wechat_pay\PluginForm\MicropayForm",
 *   },
 *   payment_type = "wechat_pay"
 * )
 */
class Micropay extends QRCodePaymentMode2 {
  use StringTranslationTrait;

  /**
   * @param string $order_id
   * @param string $auth_code
   * @param Price $total_amount
   * @param string $device_info
   * @return \Drupal\Core\Entity\EntityInterface
   */
  public function capture($order_id, $auth_code, Price $total_amount, $device_info = '') {
    if (!$this->gateway_lib) {
      $this->loadGatewayConfig();
    }
    /** @var \EasyWeChat\Payment\API $gateway; */
    $gateway = $this->gateway_lib;

    $attributes = [
      'auth_code'    => $auth_code,
      'body'         => empty($device_info) ? \Drupal::config('system.site')->get('name') . $this->t(' Order: ') . $order_id
        : $device_info . $this->t(' Order: ') . $order_id,
      // 'detail'     => 'order_items in certain format',
      'out_trade_no' => $order_id,
      'total_fee'    => $total_amount->getNumber() * 100, // WeChat Pay use Integer for its price
      'fee_type'     => $total_amount->getCurrencyCode(),
    ];

    $gateway['device_info'] = $device_info;
    $app = Factory::payment($gateway);

    try {
      /** @var \EasyWeChat\Support\Collection $response */
      $response = $app->pay($attributes);
      \Drupal::logger('commerce_wechat_pay')->notice(print_r($response, TRUE));

      if ($response['return_code'] == 'SUCCESS') {
        // Payment is successful
        $result = $response;
        if ($response['result_code'] == 'SUCCESS') {
          $payment_entity = $this->createPayment($result, 'completed');
        } else {
          if ($response['err_code'] == 'USERPAYING') {
            \Drupal::logger('commerce_wechat_pay')->notice($response['err_code_des']);
            // TODO: need to handle Wait user to input password
            $payment_entity = $this->createPayment($result, 'authorization', $order_id, 'USERPAYING', $total_amount);
          } else {
            throw new BadRequestHttpException($response['err_code']. '  ' .$response['err_code_des']);
          }
        }
        return $payment_entity;

      } else {
        // Payment is not successful
        \Drupal::logger('commerce_wechat_pay')->error(print_r($response, TRUE));
        throw new BadRequestHttpException($response['return_code'] . '  ' .$response['return_msg']);
      }
    } catch (\Exception $e) {
      // Payment is not successful
      \Drupal::logger('commerce_wechat_pay')->error($e->getMessage());
      throw new BadRequestHttpException($e->getMessage());
    }
  }

  /**
   * @param $payment_id
   * @param $order_id
   *
   * @return \Drupal\Core\Entity\EntityInterface|null|static
   */
  public function cancel($payment_id, $order_id){
    if (!$this->gateway_lib) {
      $this->loadGatewayConfig();
    }
    /** @var \EasyWeChat\Payment\API $gateway; */
    $gateway = $this->gateway_lib;

    $payment_entity=payment::load($payment_id);
    if ($payment_entity) {
      try {
        $app = Factory::payment($gateway);
        $result = $app->reverse->byOutTradeNumber($order_id);
        \Drupal::logger('commerce_wechat_pay')->notice(print_r($result, TRUE));
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
          $state = 'authorization_voided';
          $payment_entity->setState($state);
          $payment_entity->save();
          return $payment_entity;
        } else {
          \Drupal::logger('commerce_wechat_pay')->error($result['err_code_des']);
          throw new BadRequestHttpException($result['err_code'] . '  ' . $result['err_code_des']);
        }
      } catch (\Exception $e) {
        // Cancel is not successful
        \Drupal::logger('commerce_wechat_pay')->error($e->getMessage());
        throw new BadRequestHttpException($e->getMessage());
      }
    } else {
      \Drupal::logger('commerce_wechat_pay')->error('payment_id:'.$payment_id.'not exist!');
      throw new BadRequestHttpException('The order has not been paid, can not cancel it, please check it.');
      return NULL;
    }
  }

  /**
   * @param $authcode
   *
   * @return mixed
   */
  public function authCodeToOpenid($authcode) {
    if (!$this->gateway_lib) {
      $this->loadGatewayConfig();
    }
    /** @var \EasyWeChat\Payment\API $gateway ; */
    $gateway = $this->gateway_lib;
    try {
      $app = Factory::payment($gateway);
      $result = $app->authCodeToOpenid($authcode);
      if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS') {
        \Drupal::logger('commerce_wechat_pay')->notice(print_r($result,TRUE));
        return $result;
      } else {
        \Drupal::logger('commerce_wechat_pay')->error(print_r($result, TRUE));
        if ($result['return_code'] == 'SUCCESS' && $result['result_code'] == 'FAIL') {
          $error_msg = $result['err_code'].' '.$result['err_code_des'];
        }
        if($result['return_code'] == 'FAIL'){
          $error_msg = $result['return_msg'];
        }
        throw new BadRequestHttpException($error_msg);
      }
    } catch (\Exception $e) {
      // authCodeToOpenid is not successful
      \Drupal::logger('commerce_wechat_pay')->error($e->getMessage());
      throw new BadRequestHttpException($e->getMessage());
    }
  }

}
