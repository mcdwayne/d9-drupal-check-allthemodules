<?php

namespace Drupal\commerce_xem\PluginForm\Xem;

use Drupal\commerce_payment\PluginForm\PaymentGatewayFormBase;
use Drupal\Core\Form\FormStateInterface;
use Com\Tecnick\Barcode\Barcode;
use Drupal\Core\Render\Markup;
use Drupal\Component\Serialization\Json;
use Drupal\commerce_xem\XemCurrency;

/**
 * The Xem payment form on checkout. 
 */
class XemQRCodePaymentForm extends PaymentGatewayFormBase {
  use \Drupal\Core\StringTranslation\StringTranslationTrait;
  
  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $payment = $this->entity;
    /** @var \Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayInterface $payment_gateway_plugin */
    $paymentGatewayPlugin = $payment->getPaymentGateway()->getPlugin();
    $xemPublicKey = $paymentGatewayPlugin->getXemPublicKey();
    
    $order    = $payment->getOrder();
    $store_id = $order->getStoreId();
    $message = $paymentGatewayPlugin->getXemUniqueMessage($order, $paymentGatewayPlugin->getMode());
    
    $xemPrice = XemCurrency::convertToXem($order, TRUE);
    
    $data = [
      "v" => ($paymentGatewayPlugin->getMode() == 'test') ? 1 : 2, // Environnment, 1 : TestNet. 2 : MainNet. 
      "type" => 2,
      "data" => [
        "addr" => str_replace('-', '', strtoupper($xemPublicKey)),
        "amount" => $xemPrice * 1000000, // We send micro Xem
        "msg" => $message,
        "name" => "XEM payment From Drupal 8"
      ]
    ];
    
    $barcode = new Barcode();
    // generate a barcode
    $bobj = $barcode->getBarcodeObj(
        'QRCODE,H',
        Json::encode($data),
        256,
        256, 
        'black',                       
        [-2, -2, -2, -2]          
    )->setBackgroundColor('white'); 

    $form['qr'] = [
      '#type' => 'container'
    ];
    $amount = $order->getTotalPrice();
    $form['qr']['order_total'] = [
      '#type' => 'item',
      '#title' => $this->t('Total amount'),
      '#markup' => $this->t(':price :currency_code', [
        ':price' => number_format($amount->getNumber(), 2),
        ':currency_code' => $amount->getCurrencyCode()
      ]),
      '#weight' => -3
    ];
    $form['qr']['logo'] = [
      '#theme' => 'image',
      '#uri' => \Drupal::service('module_handler')
                  ->getModule('commerce_xem')->getPath() . '/images/logo.png',
      '#weight' => -2
    ]; 
    $form['qr']['commerce_qr_notice'] = [
      '#prefix' => '<div class="checkout-help">',
      '#markup' => $this->t('Please scan the QR-Code below to complete the payment'
          . ' on your mobile Xem App. We will ask you for a :amount XEM payment.', [
            ':amount' => $xemPrice
          ]),
      '#weight' => -1,
      '#suffix' => '</div>'
    ];
    $form['qr']['qrcode'] = [
      '#markup' => Markup::create($bobj->getHtmlDiv()),
      '#weight' => 0,
    ];
    
    $form['wallet'] = [
      '#type' => 'container'
    ];
    $form['wallet']['commerce_wallet_notice'] = [
      '#markup' => '<div class="checkout-help">' . $this->t('Or send Xem to our address using your Wallet.'
          . ' Don\'t miss the message.') ,
      '#weight' => 1,
    ];
    $form['wallet']['amount'] = [
      '#type' => 'item',
      '#title' => $this->t('Amount'),
      '#markup' => $this->t(':xemAmount XEM', [
        ':xemAmount' => $xemPrice
      ]),
      '#weight' => 2,
    ];
    $form['wallet']['public_key'] = [
      '#type' => 'item',
      '#title' => $this->t('Public key'),
      '#markup' => $xemPublicKey,
      '#weight' => 2,
    ];
    $form['wallet']['message'] = [
      '#type' => 'item',
      '#title' => $this->t('Message'),
      '#markup' => $message,
      '#weight' => 3,
    ];
    $form['wallet']['waiting_for'] = [
      '#type' => 'item',
      '#markup' => $this->t('Waiting for payment'),
      '#weight' => 4
    ];
  
    /*
     * We can't use the below more elegant way to get notify_url, because of this major core bug
     * https://www.drupal.org/node/2744715
     * $gateway->setNotifyUrl($this->getNotifyUrl()->toString());
     */
    global $base_url;
    $notifyUrl = str_replace('http://', '//', $base_url . '/payment/notify/' . $payment->getPaymentGatewayId());
    
    $form['#attached']['library'] = [
      'commerce_xem/xem-checkout'
    ];
    $form['#attached']['drupalSettings'] = [
      'xem' => [
        'message' => $message,
        'orderId' => $order->id(),
        'notifyUrl' => $notifyUrl
      ]
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
  }

}
