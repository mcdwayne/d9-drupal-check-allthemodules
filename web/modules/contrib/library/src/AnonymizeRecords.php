<?php

namespace Drupal\library;

use Drupal\Core\Config\ConfigFactory;
use Drupal\library\Entity\LibraryTransaction;

/**
 * Class AnonymizeRecords.
 */
class AnonymizeRecords {

  protected $config;

  /**
   * Constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config
   *   Config for settings.
   */
  public function __construct(ConfigFactory $config) {
    $this->config = $config->get('library.settings');
  }

  /**
   * Anonymize entry.
   */
  public function anonymize() {
    switch ($this->config->get('anonymize_transactions')) {
      case 'daily':
        $interval = 86400;
        break;

      case 'weekly':
        $interval = 86400 * 7;
        break;

      case 'monthly':
        $interval = 86400 * 30;
        break;

      case  'never':
      default:
        $interval = 0;
        break;
    }

    $lastCheck = \Drupal::state()->get('library_last_anonymization');
    if ($interval > 0 && strtotime('today') + $interval >= $lastCheck) {
      $items = \Drupal::entityQuery('library_item')
        ->condition('library_status', LibraryItemInterface::ITEM_AVAILABLE)
        ->execute();
      $this->processBatch($items);
      \Drupal::state()->set('library_last_anonymization', strtotime('today'));
    }
  }

  /**
   * Process batch.
   *
   * @param array $items
   *   Items to anonymize.
   */
  private function processBatch(array $items) {
    $results = [];
    foreach ($items as $item) {
      $transactions = \Drupal::entityQuery('library_transaction')
        ->Exists('uid')
        ->condition('library_item', $item)
        ->execute();
      $transactionEntities = LibraryTransaction::loadMultiple($transactions);
      foreach ($transactionEntities as $transaction) {
        $transaction->set('uid', NULL);
        $results[] = $transaction->save();
      }
    }
    if ($results) {
      \Drupal::logger('library')
        ->notice('@count transactions anonymized.', [
          '@count' => count($results),
        ]
        );
    }
  }

}
