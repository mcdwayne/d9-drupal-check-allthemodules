<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * Xero CreditNote type.
 *
 * @DataType(
 *   id = "xero_credit_note",
 *   label = @Translation("Xero Credit Note"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\CreditDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class CreditNote extends XeroTypeBase {

  static public $guid_name = 'CreditNoteID';
  static public $plural_name = 'CreditNotes';
  static public $label = 'CreditNoteNumber';
  static public $xero_name = 'CreditNote';

  /**
   * {@inheritdoc}
   */
  public function view() {
    $header = [
      $this->t('Description'),
      $this->t('Quantity'),
      $this->t('Unit amount'),
      $this->t('Account code'),
      $this->t('Line amount'),
    ];
    $className = substr($this->getName(), 5);
    $rows = [];

    $build = [
      '#theme' => $this->getName(),
      '#credit' => $this->getValue(),
      '#attributes' => [
        'class' => ['xero-item', 'xero-item--' . $className],
      ],
      '#contact' => $this->get('Contact')->view(),
      '#items' => [
        '#theme' => 'table',
        '#header' => $header,
      ]
    ];

    $lineitems = $this->get('LineItems');
    /** @var \Drupal\xero\Plugin\DataType\LineItem $lineitem */
    foreach ($lineitems as $lineitem) {
      $row = [];

      $row[] = $lineitem->get('Description')->getString();
      $row[] = $lineitem->get('Quantity')->getString();
      $row[] = $lineitem->get('UnitAmount')->getString();
      $row[] = $lineitem->get('AccountCode')->getString();
      $row[] = $lineitem->get('LineAmount')->getString();

      $rows[] = $row;
    }

    $rows[] = ['', '', '', $this->t('Sub-Total'), $this->get('SubTotal')->getString()];
    $rows[] = ['', '', '', $this->t('Tax'), $this->get('TotalTax')->getString()];
    $rows[] = ['', '', '', $this->t('Total'), $this->get('Total')->getString()];

    $build['#items']['rows'] = $rows;

    return $build;
  }

}
