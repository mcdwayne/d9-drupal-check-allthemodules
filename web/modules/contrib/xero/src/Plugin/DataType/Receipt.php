<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * Xero Receipt type.
 *
 * @DataType(
 *   id = "xero_receipt",
 *   label = @Translation("Xero Receipt"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\ReceiptDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class Receipt extends XeroTypeBase {

  static public $guid_name = 'ReceiptID';
  static public $xero_name = 'Receipt';
  static public $plural_name = 'Receipts';
  static public $label = 'ReceiptNumber';

  /**
   * {@inheritdoc}
   */
  public function view() {
    $header = [
      $this->t('Description'),
      $this->t('Account code'),
      $this->t('Quantity'),
      $this->t('Unit amount'),
      $this->t('Tax type'),
      $this->t('Line amount')
    ];
    $rows = [];

    $className = substr($this->getName(), 5);

    $build = [
      '#theme' => $this->getName(),
      '#receipt' => $this->getValue(),
      '#user' => $this->get('User')->view(),
      '#contact' => $this->get('Contact')->view(),
      '#items' => [
        '#theme' => 'table',
        '#header' => $header,
      ],
      '#attributes' => [
        'class' => ['xero-item', 'xero-item--' . $className],
      ],
    ];

    foreach ($this->get('LineItems') as $lineitem) {
      /** @var \Drupal\xero\Plugin\DataType\LineItem $lineitem */
      $rows[] = [
        $lineitem->get('Description')->getString(),
        $lineitem->get('AccountCode')->getString(),
        $lineitem->get('Quantity')->getString(),
        $lineitem->get('UnitAmount')->getString(),
        $lineitem->get('TaxType')->getString(),
        $lineitem->get('LineAmount')->getString(),
      ];
    }

    $build['#items']['#rows'] = $rows;

    return $build;
  }

}
