<?php

namespace Drupal\commerce_wechat_pay\PluginForm;

use Drupal\commerce_payment\PluginForm\PaymentOffsiteForm as BasePaymentOffsiteForm;
use Drupal\Core\Form\FormStateInterface;
use Com\Tecnick\Barcode\Barcode;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

class QRCodePaymentForm extends BasePaymentOffsiteForm {
  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    /** @var \Drupal\commerce_payment\Entity\PaymentInterface $payment */
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $payment_gateway_plugin = $payment->getPaymentGateway()->getPlugin();

    $order    = $payment->getOrder();
    $price    = $payment->getAmount();
    $store_id = $order->getStoreId();

    try {
      /** @var \Drupal\commerce_payment\Entity\Payment $payment_entity */
      $payment_entity = $payment_gateway_plugin->requestQRCode((string) $order->id(), $price, null, $store_id);

      $barcode = new Barcode();
      // generate a barcode
      $bobj = $barcode->getBarcodeObj(
        'QRCODE,H',                     // barcode type and additional comma-separated parameters
        $payment_entity->getRemoteState(),          // data string to encode
        -4,                             // bar height (use absolute or negative value as multiplication factor)
        -4,                             // bar width (use absolute or negative value as multiplication factor)
        'black',                        // foreground color
        [-2, -2, -2, -2]           // padding (use absolute or negative values as multiplication factors)
      )->setBackgroundColor('white'); // background color

      $form['commerce_message'] = [
        '#markup' => '<div class="checkout-help">' . $this->t('Please scan the QR-Code below to complete the payment on your mobile WeChat App.') ,
        '#weight' => -10,
      ];

      $form['qrcode'] = [
        '#markup' => Markup::create($bobj->getHtmlDiv()),
      ];

    } catch (\Exception $e) {
      $form['commerce_message'] = [
        '#markup' => '<div class="checkout-help">' . $this->t('WeChat QR-Code is not available at the moment. Message from WeChat service: ' . $e->getMessage()),
        '#weight' => -10,
      ];
    }

    return $form;
  }

}
