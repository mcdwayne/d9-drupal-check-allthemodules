<?php

namespace Drupal\library\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\library\Entity\LibraryItem;
use Drupal\library\Entity\LibraryTransaction;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

/**
 * Class OverdueOverview.
 *
 * @package Drupal\library\Controller
 */
class OverdueOverview extends ControllerBase {

  /**
   * List.
   *
   * @return string
   *   Return Hello string.
   */
  public function listing() {
    $renderer = \Drupal::service('renderer');

    $data['elements'] = [
      '#type' => 'table',
      '#title' => t('Item history'),
      '#header' => ['Item', 'Patron', 'Due Date', 'Last comment', 'Edit'],
    ];

    $items = \Drupal::entityQuery('library_item')
      ->condition('library_status', LibraryItem::ITEM_UNAVAILABLE)
      ->execute();

    $loadedItems = entity_load_multiple('library_item', $items);

    foreach ($loadedItems as $item) {
      /** @var \Drupal\library\Entity\LibraryItem $item */
      $format_title = '';
      if ($item->get('nid')->getValue()) {
        $node = Node::load($item->get('nid')->getValue()[0]['target_id']);
        $label = $node->getTitle();

        if ($item->get('barcode')->value) {
          $label .= ' (' . $item->get('barcode')->value . ')';
        }
        $format_title = [
          '#type' => 'link',
          '#title' => $label,
          '#url' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()]),
        ];
        $format_title = $renderer->render($format_title);

      }

      $transaction = $item->getLatestTransactionDue();

      if ($transaction && count($transaction) == 1) {
        $loadedTransaction = LibraryTransaction::load(array_pop($transaction));
        $patronName = '';
        if ($loadedTransaction->get('uid')->getValue()) {
          $patron = User::load($loadedTransaction->get('uid')->getValue()[0]['target_id']);
          if ($patron) {
            $patronName = [
              '#type' => 'link',
              '#title' => $patron->getDisplayName(),
              '#url' => Url::fromRoute('entity.user.canonical', ['user' => $patron->id()]),
            ];
            $patronName = $renderer->render($patronName);

            $patronId = $patron->id();
          }
        }

        $due = '';
        if ($loadedTransaction->get('due_date')->value > 0) {
          $due = \Drupal::service('date.formatter')->format($loadedTransaction->get('due_date')->value, 'short');
        }

        $editLink = [
          '#type' => 'link',
          '#title' => $this->t('Add note'),
          '#url' => Url::fromRoute('library.edit_transaction', ['transaction' => $loadedTransaction->id()]),
        ];

        $data['elements']['#rows'][$patronId . '_' . $item->id()] = [
          $format_title,
          $patronName,
          $due,
          $this->formatMarkup($loadedTransaction->get('notes')->value),
          \Drupal::service('renderer')->render($editLink),
        ];
        ksort($data['elements']['#rows']);
      }
    }

    return $data;
  }

  /**
   * Format markup.
   *
   * @param string $data
   *   Data for render array.
   *
   * @return string
   *   Formatted HTML.
   */
  private function formatMarkup($data) {
    $escaped = ['#markup' => nl2br($data)];
    $formatted = \Drupal::service('renderer')->render($escaped);
    return $formatted;
  }

}
