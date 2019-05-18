<?php

namespace Drupal\commerce_wechat_pay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_price\Price;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use EasyWeChat\Factory;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides WeChat JSAPIpay gateway.
 *
 * @link https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=7_1
 *
 * @CommercePaymentGateway(
 *   id = "jsapipay",
 *   label = "WeChat JSAPIpay",
 *   display_label = "WeChat JSAPIpay",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_wechat_pay\PluginForm\JSAPIpayForm",
 *   },
 *   payment_type = "wechat_pay"
 * )
 */
class JSAPIpay extends QRCodePaymentMode2 {

  use StringTranslationTrait;

  /**
   * Request Unified Order.
   *
   * @param string $openid
   *   Customer's openid.
   * @param string $order_id
   *   Order ID.
   * @param \Drupal\commerce_price\Price $total_amount
   *   Order's total amount.
   * @param string|null $notify_url
   *   Notify URL.
   * @param string $sub_openid
   *   Customer's sub openid.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Payment entity.
   */
  public function requestUnifiedOrder($openid, $order_id, Price $total_amount, $notify_url = NULL, $sub_openid = NULL) {
    if (!$this->gateway_lib) {
      $this->loadGatewayConfig();
    }

    $gateway = $this->gateway_lib;

    if (!$notify_url) {
      global $base_url;
      $notify_url = $base_url . '/payment/notify/' . $this->entityId;
    }

    $attributes = [
      'trade_type' => 'JSAPI',
      'body' => \Drupal::config('system.site')->get('name') . $this->t('Order:') . $order_id,
      'out_trade_no' => $order_id . '',
      'total_fee' => $total_amount->getNumber() * 100,
      // WeChat Pay use Integer for its price.
      'fee_type' => $total_amount->getCurrencyCode(),
      'notify_url' => $notify_url,
      'openid' => $openid,
      'sub_openid' => $sub_openid
    ];

    $app = Factory::payment($gateway);

    try {
      $response = $app->order->unify($attributes);
      if ($response['return_code'] == 'SUCCESS' && $response['result_code'] == 'SUCCESS') {
        $prepayId = $response['prepay_id'];
        $jsApiParameters = $app->jssdk->bridgeConfig($prepayId, TRUE);
        $payment_entity = $this->createPayment(
          $response,
          'authorization',
          $order_id,
          $jsApiParameters,
          $total_amount
        );
        return $payment_entity;
      }
      else {
        \Drupal::logger('commerce_wechat_pay')->error(print_r($response, TRUE));
        throw new BadRequestHttpException(
          $response['err_code_des'] . ': ' . $response['return_msg']
        );
      }
    }
    catch (\Exception $e) {
      \Drupal::logger('commerce_wechat_pay')->error($e->getMessage());
      throw new BadRequestHttpException($e->getMessage());
    }

  }

}
