<?php

namespace Drupal\commerce_banklink\Plugin\Commerce\PaymentGateway;

use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Exception\PaymentGatewayException;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OffsitePaymentGatewayBase;
use Drupal\commerce_price\Price;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\Request;


/**
 * Provides the Off-site Redirect payment gateway.
 *
 * @CommercePaymentGateway(
 *   id = "generic_banklink_redirect",
 *   label = "Offsite banklink gateway",
 *   display_label = @Translation("Banklink"),
 *    forms = {
 *     "offsite-payment" = "Drupal\commerce_banklink\PluginForm\OffsiteRedirect\PaymentOffsiteForm",
 *   },
 * )
 */
class GenericBanklinkPaymentGateway extends OffsitePaymentGatewayBase {


  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
        'bank' => 'LHV',
        'merchant_id' => '',
        'private_key' => '',
        'public_key' => '',
        'api_url' => '',
      ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['bank'] = [
      '#type' => 'select',
      '#title' => $this->t('BANK'),
      '#options' => [
        'DANSKE' => 'Danske Bank',
        'KREDIIDIPANK' => 'Krediidipank',
        'LHV' => 'LHV',
        'SEB' => 'SEB',
        'SWEDBANK' => 'Swedbank',
        'NORDEA' => 'Nordea'
      ],
      '#default_value' => $this->configuration['bank'],
      '#required' => TRUE,
    ];
    $form['merchant_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Merchant id'),
      '#default_value' => $this->configuration['merchant_id'],
      '#required' => TRUE,
    ];
    $form['private_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Merchant private key'),
      '#default_value' => $this->configuration['private_key'],
      '#required' => TRUE,
    ];
    $form['public_key'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Bank public key'),
      '#default_value' => $this->configuration['public_key'],
      '#required' => TRUE,
    ];
    $form['api_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Bank API URL'),
      '#default_value' => $this->configuration['api_url'],
      '#required' => TRUE,
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
      $this->configuration['bank'] = $values['bank'];
      $this->configuration['merchant_id'] = $values['merchant_id'];
      $this->configuration['private_key'] = $values['private_key'];
      $this->configuration['public_key'] = $values['public_key'];
      $this->configuration['api_url'] = $values['api_url'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getDisplayLabel() {
    return $this->configuration['bank'] .' '. parent::getDisplayLabel();
  }

  /**
   * {@inheritdoc}
   */
  public function onReturn(OrderInterface $order, Request $request) {

    if ($this->verifyBankSignature($request->query->all())) {
      switch ($request->query->get('VK_SERVICE')) {
        case '1111': // Payment success

          $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
          /** @var \Drupal\commerce_payment\Entity\Payment $payment */
          $payment = $payment_storage->create([
            'state' => 'authorization',
            'amount' => $order->getTotalPrice(),
            'payment_gateway' => $this->entityId,
            'order_id' => $order->id(),
            'test' => $this->getMode() == 'test',
            'remote_id' => $request->query->get('VK_T_NO'),
            'remote_state' => 'Success',
            'authorized' => $this->time->getRequestTime(),
          ]);
          $payment->save();

          drupal_set_message($this->t('Payment was successfully processed'));

          break;
        case '1911': // Payment failure
          $payment_storage = $this->entityTypeManager->getStorage('commerce_payment');
          /** @var \Drupal\commerce_payment\Entity\Payment $payment */
          $payment = $payment_storage->create([
            'state' => 'authorization_voided',
            'amount' => $order->getTotalPrice(),
            'payment_gateway' => $this->entityId,
            'order_id' => $order->id(),
            'test' => $this->getMode() == 'test',
            'remote_state' => 'Failure',
          ]);
          $payment->save();

          throw new PaymentGatewayException('Payment failed');

          break;
      }
    } else {
      throw new PaymentGatewayException('Invalid bank response');
    }

  }

  /**
   * Returns whether the bank signature matches the sent MAC
   *
   * @param array $data
   * @return bool
   */
  private function verifyBankSignature(array $data) {

    if (!empty($data['VK_SERVICE']) && !empty($data['VK_MAC'])) {
      $signature_data = array();
      if ($data['VK_SERVICE'] == '1111') {
        $signature_data = $this->padData([
          $data['VK_SERVICE'],
          $data['VK_VERSION'],
          $data['VK_SND_ID'],
          $data['VK_REC_ID'],
          $data['VK_STAMP'],
          $data['VK_T_NO'],
          $data['VK_AMOUNT'],
          $data['VK_CURR'],
          $data['VK_REC_ACC'],
          $data['VK_REC_NAME'],
          $data['VK_SND_ACC'],
          $data['VK_SND_NAME'],
          $data['VK_REF'],
          $data['VK_MSG'],
          $data['VK_T_DATETIME'],
        ]);
      }

      if ($data['VK_SERVICE'] == '1911') {
        $signature_data = $this->padData([
          $data['VK_SERVICE'],
          $data['VK_VERSION'],
          $data['VK_SND_ID'],
          $data['VK_REC_ID'],
          $data['VK_STAMP'],
          $data['VK_REF'],
          $data['VK_MSG'],
        ]);
      }

      $signature = base64_decode($data['VK_MAC']);
      $public_key = @openssl_get_publickey($this->configuration['public_key']);
      $out = @openssl_verify($signature_data, $signature, $public_key);
      @openssl_free_key($public_key);

      return ($out == 1);
    }

    return false;
  }

  /**
   * Returns the required padded output of the data array (3 digit variable stating the length of the element before every string)
   *
   * @param array $data
   * @return string
   */
  private function padData(array $data) {

    $output = '';

    foreach ($data as $element) {
      $output .= str_pad(mb_strlen($element), 3, '0', STR_PAD_LEFT) . $element;
    }

    return $output;
  }

}
