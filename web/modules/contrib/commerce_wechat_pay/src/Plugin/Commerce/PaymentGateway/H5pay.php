<?php

namespace Drupal\commerce_wechat_pay\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_price\Price;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use EasyWeChat\Factory;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * Provides WeChat H5pay gateway.
 * @link https://pay.weixin.qq.com/wiki/doc/api/H5.php?chapter=15_1
 *
 * @CommercePaymentGateway(
 *   id = "h5pay",
 *   label = "WeChat H5pay",
 *   display_label = "WeChat H5pay",
 *   forms = {
 *     "offsite-payment" = "Drupal\commerce_wechat_pay\PluginForm\H5payForm",
 *   },
 *   payment_type = "wechat_pay"
 * )
 */
class H5pay extends QRCodePaymentMode2 {
  use StringTranslationTrait;

  /**
   * Request Unified Order.
   *
   * @param string $order_id
   *   Order ID.
   * @param \Drupal\commerce_price\Price $total_amount
   *   Order's total amount.
   * @param string|null $notify_url
   *   Notify URL.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   Payment entity.
   */
  public function requestH5UnifiedOrder($order_id, Price $total_amount, $notify_url = NULL) {
    if (!$this->gateway_lib) {
      $this->loadGatewayConfig();
    }

    $gateway = $this->gateway_lib;

    if (!$notify_url) {
      global $base_url;
      $notify_url = $base_url . '/payment/notify/' . $this->entityId;
    }

    $attributes = [
      'trade_type' => 'MWEB',
      'body' => \Drupal::config('system.site')->get('name') . $this->t('Order:') . $order_id,
      'out_trade_no' => $order_id . '',
      'total_fee' => $total_amount->getNumber() * 100,
      // WeChat Pay use Integer for its price.
      'fee_type' => $total_amount->getCurrencyCode(),
      'notify_url' => $notify_url,
    ];

    $app = Factory::payment($gateway);

    try {
      $response = $app->order->unify($attributes);
      if ($response['return_code'] == 'SUCCESS' && $response['result_code'] == 'SUCCESS') {
        $payment_entity = $this->createPayment(
          $response,
          'authorization',
          $order_id,
          'USERPAYING',
          $total_amount
        );
        return $payment_entity;
      }
      else {
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
