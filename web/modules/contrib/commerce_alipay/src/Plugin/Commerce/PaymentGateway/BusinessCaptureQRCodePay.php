<?php

namespace Drupal\commerce_alipay\Plugin\Commerce\PaymentGateway;

/**
 * Provides Alipay gateway for customer to scan QR-Code to pay.
 * @link https://doc.open.alipay.com/docs/doc.htm?treeId=194&articleId=105072&docType=1
 *
 * @CommercePaymentGateway(
 *   id = "alipay_business_capture_qrcode_pay",
 *   label = "Alipay - Business Capture QR-Code to Pay",
 *   display_label = "Alipay - Business Capture QR-Code to Pay",
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_alipay\PluginForm\CaptureQRCodeForm",
 *   },
 *   payment_type = "alipay"
 * )
 */
class BusinessCaptureQRCodePay extends CustomerScanQRCodePay {

  // All the functions are same as parent class.

}
