<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * Xero Invoice type.
 *
 * @DataType(
 *   id = "xero_invoice",
 *   label = @Translation("Xero Invoice"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\InvoiceDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class Invoice extends XeroTypeBase {

  static public $guid_name = 'InvoiceID';
  static public $xero_name = "Invoice";
  static public $plural_name = 'Invoices';
  static public $label = 'InvoiceNumber';

  /**
   * {@inheritdoc}
   */
  public function view() {
    $header = [
      $this->t('Description'),
      $this->t('Quantity'),
      $this->t('Unit amount'),
      $this->t('Account code'),
      $this->t('Tax type'),
      $this->t('Tax amount'),
      $this->t('Line amount'),
    ];
    $payment_header = [
      $this->t('Type'),
      $this->t('Status'),
      $this->t('Amount'),
      $this->t('Date'),
    ];
    $className = substr($this->getName(), 5);
    $rows = [];
    $payment_rows = [];
    $contact = $this->get('Contact');

    $build = [
      '#theme' => $this->getName(),
      '#invoice' => $this->getValue(),
       '#attributes' => [
        'class' => ['xero-item', 'xero-item--' . $className],
      ],
      '#contact' => is_object($contact) ? $contact->view() : '',
      '#items' => [
        '#theme' => 'table',
        '#header' => $header,
      ],
      '#payments' => [
        '#theme' => 'table',
        '#header' => $payment_header,
      ],
    ];

    $lineitems = $this->get('LineItems');
    /** @var \Drupal\xero\Plugin\DataType\LineItem $lineitem */
    foreach ($lineitems as $lineitem) {
      $row = [];

      $row[] = $lineitem->get('Description')->getString();
      $row[] = $lineitem->get('Quantity')->getString();
      $row[] = $lineitem->get('UnitAmount')->getString();
      $row[] = $lineitem->get('AccountCode')->getString();
      $row[] = $lineitem->get('TaxType')->getString();
      $row[] = $lineitem->get('TaxAmount')->getString();
      $row[] = $lineitem->get('LineAmount')->getString();

      $rows[] = $row;
    }

    $rows[] = ['', '', '', '', '', $this->t('Sub-Total'), $this->get('SubTotal')->getString()];
    $rows[] = ['', '', '', '', '', $this->t('Tax'), $this->get('TotalTax')->getString()];
    $rows[] = ['', '', '', '', '', $this->t('Total'), $this->get('Total')->getString()];

    $build['#items']['rows'] = $rows;

    $payments = $this->get('Payments');
    /** @var \Drupal\xero\Plugin\DataType\Payment $payment */
    foreach ($payments as $payment) {
      $build['payments']['#rows'][] = [
        $payment->get('PaymentType')->getString(),
        $payment->get('Status')->getString(),
        $payment->get('Amount')->getString(),
        $payment->get('Date')->getString(),
      ];
    }

    return $build;
  }
}
