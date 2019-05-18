<?php

namespace Drupal\xero\Plugin\DataType;

/**
 * Xero Journal type.
 *
 * @DataType(
 *   id = "xero_journal",
 *   label = @Translation("Xero Journal"),
 *   definition_class = "\Drupal\xero\TypedData\Definition\JournalDefinition",
 *   list_class = "\Drupal\xero\Plugin\DataType\XeroItemList"
 * )
 */
class Journal extends XeroTypeBase {

  static public $guid_name = 'JournalID';
  static public $xero_name = 'Journal';
  static public $plural_name = 'Journals';
  static public $label = 'JournalNumber';

  /**
   * {@inheritdoc}
   */
  public function view() {
    $header = [
      $this->t('Code'),
      $this->t('Type'),
      $this->t('Name'),
      $this->t('Description'),
      $this->t('NetAmount'),
      $this->t('GrossAmount'),
      $this->t('TaxAmount'),
      $this->t('TaxType'),
      $this->t('TaxName'),
    ];
    $rows = [];
    $className = substr($this->getName(), 5);

    $item = [
      '#theme' => $this->getName(),
      '#journal' => $this->getValue(),
      '#items' => [
        '#theme' => 'table',
        '#header' => $header,
      ],
      '#attributes' => [
        'class' => ['xero-item', 'xero-item--' . $className],
      ],
    ];

    foreach ($this->get('JournalLines') as $journal) {
      /** @var \Drupal\xero\Plugin\DataType\JournalLine $journal */
      $rows[] = [
        $journal->get('AccountCode')->getString(),
        $journal->get('AccountType')->getString(),
        $journal->get('AccountName')->getString(),
        $journal->get('Description')->getString(),
        $journal->get('NetAmount')->getString(),
        $journal->get('GrossAmount')->getString(),
        $journal->get('TaxAmount')->getString(),
        $journal->get('TaxType')->getString(),
        $journal->get('TaxName')->getString(),
      ];
    }

    return $item;
  }
}
