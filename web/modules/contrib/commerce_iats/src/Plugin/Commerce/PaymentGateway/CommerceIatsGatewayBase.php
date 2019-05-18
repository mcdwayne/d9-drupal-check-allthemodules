<?php

namespace Drupal\commerce_iats\Plugin\Commerce\PaymentGateway;

use CommerceGuys\Intl\Formatter\NumberFormatterInterface;
use Drupal\commerce_payment\Entity\PaymentMethodInterface;
use Drupal\commerce_payment\Plugin\Commerce\PaymentGateway\OnsitePaymentGatewayBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class CommerceIatsGatewayBase.
 */
abstract class CommerceIatsGatewayBase extends OnsitePaymentGatewayBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'transcenter' => '',
      'processor' => '',
      'gateway_id' => '',
      'processing_type' => 'hosted',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['transcenter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Transaction Center ID'),
      '#description' => $this->t('Your Transaction Center ID number for your 1stPayGateway account.'),
      '#default_value' => $this->configuration['transcenter'],
      '#required' => TRUE,
    ];

    $form['processor'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Processor ID'),
      '#description' => $this->t('The ID number for a MID/TID combination on your 1stPayGateway account.'),
      '#default_value' => $this->configuration['processor'],
      '#required' => TRUE,
    ];

    $form['gateway_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Gateway ID'),
      '#description' => $this->t('The Gateway ID for your 1stPayGateway account.'),
      '#default_value' => $this->configuration['gateway_id'],
      '#required' => TRUE,
    ];

    $form['processing_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Processing type'),
      '#description' => $this->t('The processing type to utilize for this gateway.'),
      '#default_value' => $this->configuration['processing_type'],
      '#required' => TRUE,
      '#options' => [
        'hosted' => $this->t('Hosted form'),
        'direct_submission' => $this->t('Direct submission'),
      ],
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
      $this->configuration['transcenter'] = $values['transcenter'];
      $this->configuration['processor'] = $values['processor'];
      $this->configuration['gateway_id'] = $values['gateway_id'];
      $this->configuration['processing_type'] = $values['processing_type'];
    }
  }

  /**
   * Determines if the payment gateway is operating with hosted form processing.
   *
   * @return bool
   *   Indicates if the payment gateway is operating with hosted form
   *   processing.
   */
  public function isHosted() {
    return $this->configuration['processing_type'] == 'hosted';
  }

  /**
   * Sets billing info into a data array.
   *
   * @param \Drupal\commerce_payment\Entity\PaymentMethodInterface $payment_method
   *   The payment method.
   * @param array $data
   *   Optional. An existing data array, or a new one will be generated.
   *
   * @return array
   *   Data array with billing info set.
   */
  protected function setPaymentMethodBillingInfo(PaymentMethodInterface $payment_method, array $data = []) {
    /** @var \Drupal\address\AddressInterface $address */
    $address = $payment_method->getBillingProfile()->address->first();

    $data['ownerName'] = $address->getGivenName() . ' ' . $address->getFamilyName();
    $data['ownerStreet'] = $address->getAddressLine1();
    $data['ownerStreet2'] = $address->getAddressLine2();
    $data['ownerCity'] = $address->getLocality();
    $data['ownerState'] = $address->getAdministrativeArea();
    $data['ownerZip'] = $address->getPostalCode();
    $data['ownerCountry'] = $address->getCountryCode();
    $data['ownerEmail'] = $payment_method->getOwner()->getEmail();

    return $data;
  }

  /**
   * Formats an amount.
   *
   * @param string $amount
   *   The raw amount.
   *
   * @return string
   *   The formatted amount.
   */
  protected function formatAmount($amount) {
    return $this->getAmountFormatter()->format($amount);
  }

  /**
   * Gets a number formatter ready to handle amounts.
   *
   * @return \CommerceGuys\Intl\Formatter\NumberFormatterInterface
   *   The number formatter.
   */
  protected function getAmountFormatter() {
    /** @var \Drupal\commerce_price\NumberFormatterFactoryInterface $formatterFactory */
    $formatterFactory = \Drupal::service('commerce_price.number_formatter_factory');
    $numberFormatter = $formatterFactory->createInstance(NumberFormatterInterface::DECIMAL);
    return $numberFormatter->setGroupingUsed(FALSE)
      ->setMinimumFractionDigits(2)
      ->setMaximumFractionDigits(2);
  }

  /**
   * Gets the commerce iATS rest gateway service.
   *
   * @return \Drupal\commerce_iats\Rest\GatewayInterface
   *   The commerce iATS rest gateway service.
   */
  protected function getGateway() {
    return \Drupal::service('commerce_iats.rest_gateway_factory')
      ->getGateway(
        $this->configuration['gateway_id'],
        $this->configuration['processor']
      );
  }

  /**
   * Gets the commerce iATS service.
   *
   * @return \Drupal\commerce_iats\CommerceIats
   *   The commerce iATS service.
   */
  protected function getCommerceIats() {
    return \Drupal::service('commerce_iats');
  }

}
