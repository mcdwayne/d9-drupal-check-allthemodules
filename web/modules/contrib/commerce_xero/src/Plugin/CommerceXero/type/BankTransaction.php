<?php

namespace Drupal\commerce_xero\Plugin\CommerceXero\type;

use Drupal\address\FieldHelper;
use Drupal\commerce_order\Entity\Order;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_payment\Entity\PaymentInterface;
use Drupal\commerce_xero\Entity\CommerceXeroStrategyInterface;
use Drupal\commerce_xero\Plugin\CommerceXero\CommerceXeroDataTypePluginBase;
use Drupal\Core\TypedData\TypedDataTrait;
use Drupal\Core\Url;

/**
 * Bank Transaction data type processor plugin.
 *
 * @CommerceXeroDataType(
 *   id = "commerce_xero_bank_transaction",
 *   label = @Translation("Bank Transaction"),
 *   type = "xero_bank_transaction",
 *   settings = { }
 * )
 */
class BankTransaction extends CommerceXeroDataTypePluginBase {

  use TypedDataTrait;

  /**
   * {@inheritdoc}
   */
  public function make(PaymentInterface $payment, CommerceXeroStrategyInterface $strategy) {
    $configuration = $this->getConfiguration();
    $typedDataManager = $this->getTypedDataManager();
    $definition = $this->typedDataManager
      ->createDataDefinition($configuration['type']);

    // Sets the payment date depending on the payment state.
    $date = $payment->isCompleted() ? $payment->getCompletedTime() : \time();

    $data = [
      'Type' => 'RECEIVE',
      'BankAccount' => ['Code' => $strategy->get('bank_account')],
      'Contact' => $this->getDefaultContactValues($payment->getOrder()),
      'Date' => date('Y-m-d', $date),
      'Total' => $payment->getAmount()->getNumber(),
      'Reference' => $payment->getOrder()->id(),
      'Url' => Url::fromUri(
        'entity:commerce_order/' . $payment->getOrder()->id(),
        ['absolute' => TRUE])->toString(),
      'LineAmountTypes' => 'Inclusive',
      'LineItems' => [
        [
          'Description' => 'Order ' . $payment->getOrder()->id() . ' Payment ' . $payment->id(),
          'UnitAmount' => $payment->getAmount()->getNumber(),
          'AccountCode' => $strategy->get('revenue_account'),
        ],
      ],
    ];

    return $typedDataManager->create($definition, $data);
  }

  /**
   * Get a default contact values from the order.
   *
   * @param \Drupal\commerce_order\Entity\OrderInterface $order
   *   The order entity.
   *
   * @return array
   *   An array to set on the Contact property.
   */
  protected function getDefaultContactValues(OrderInterface $order) {
    $profile = $order->getBillingProfile();
    if ($profile->hasField('address')) {
      // Gets the Contact information from the Address fields.
      /** @var \Drupal\address\AddressInterface $address */
      $address = $profile->address[0];
      $name = $address->getOrganization() ? $address->getOrganization() : $address->getGivenName() . ' ' . $address->getFamilyName();

      return [
        'FirstName' => $address->getGivenName(),
        'LastName' => $address->getFamilyName(),
        'Name' => $name,
        'EmailAddress' => $order->getEmail(),
      ];
    }

    // Otherwise get basic contact information from the order.
    return [
      'Name' => $order->getCustomer()->getAccountName(),
      'EmailAddress' => $order->getEmail(),
    ];
  }

}
