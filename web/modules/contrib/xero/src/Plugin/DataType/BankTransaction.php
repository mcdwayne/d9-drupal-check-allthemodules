<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * Xero BankTransaction type.
 *
 * @DataType(
 *   id = "xero_bank_transaction",
 *   label = @Translation("Xero Bank Transaction"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\BankTransactionDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class BankTransaction extends XeroTypeBase {

  static public $guid_name = 'BankTransactionID';
  static public $xero_name = 'BankTransaction';
  static public $plural_name = 'BankTransactions';
  static public $label;

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
      $this->t('Line amount'),
    ];
    $className = substr($this->getName(), 5);
    $rows = [];

    $build = [
      '#theme' => $this->getName(),
      '#transaction' => $this->getValue(),
      '#attributes' => [
        'class' => ['xero-item', 'xero-item--' . $className],
      ],
      '#contact' => $this->get('Contact')->view(),
      '#items' => [
        '#theme' => 'table',
        '#header' => $header,
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
      $row[] = $lineitem->get('LineAmount')->getString();

      $rows[] = $row;
    }

    $rows[] = ['', '', '', '', $this->t('Sub-Total'), $this->get('SubTotal')->getString()];
    $rows[] = ['', '', '', '', $this->t('Tax'), $this->get('TotalTax')->getString()];
    $rows[] = ['', '', '', '', $this->t('Total'), $this->get('Total')->getString()];

    $build['#items']['rows'] = $rows;

    return $build;
  }
}
