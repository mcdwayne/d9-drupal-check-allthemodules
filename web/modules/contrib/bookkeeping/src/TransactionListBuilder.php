<?php

namespace Drupal\bookkeeping;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\bookkeeping\Plugin\Field\FieldType\BookkeepingEntryItem;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Link;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of Transaction entities.
 *
 * @ingroup bookkeeping
 */
class TransactionListBuilder extends EntityListBuilder {

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('commerce_price.currency_formatter')
    );
  }

  /**
   * Construct the transaction list builder.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, CurrencyFormatterInterface $currency_formatter) {
    parent::__construct($entity_type, $storage);
    $this->currencyFormatter = $currency_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('Transaction ID');
    $header['batch'] = $this->t('Batch');
    $header['description'] = $this->t('Description / Account');
    $header['debit'] = $this->t('Debit');
    $header['credit'] = $this->t('Credit');
    $header['total'] = '';
    return $header;
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\bookkeeping\Entity\Transaction */
    $row['id'] = $entity->id();
    $row['batch'] = $entity->get('batch')->value;
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.bookkeeping_transaction.canonical',
      ['bookkeeping_transaction' => $entity->id()]
    );
    $total = $entity->getTotal();
    $row['amount'] = [
      'colspan' => 3,
      'class' => ['amount'],
      'data' => [
        '#markup' => $this->currencyFormatter->format($total->getNumber(), $total->getCurrencyCode()),
      ],
    ];
    return $row;
  }

  /**
   * Build the row for a specific entry.
   *
   * @param \Drupal\bookkeeping\Plugin\Field\FieldType\BookkeepingEntryItem $item
   *   The entry item.
   *
   * @return array
   *   The row render array.
   */
  protected function buildEntryRow(BookkeepingEntryItem $item) {
    $formatted_amount = $this->currencyFormatter
      ->format($item->amount, $item->currency_code);
    return [
      'id' => ['colspan' => 2],
      'name' => [
        'class' => ['account'],
        'data' => ['#markup' => $item->entity->label()],
      ],
      'debit' => [
        'class' => ['amount'],
        'data' => [
          '#markup' => $item->type == BookkeepingEntryItem::TYPE_DEBIT ? $formatted_amount : '',
        ],
      ],
      'credit' => [
        'class' => ['amount'],
        'data' => [
          '#markup' => $item->type == BookkeepingEntryItem::TYPE_CREDIT ? $formatted_amount : '',
        ],
      ],
      'total' => '',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $build['table'] = [
      '#type' => 'table',
      '#header' => $this->buildHeader(),
      '#title' => $this->getTitle(),
      '#rows' => [],
      '#empty' => $this->t('There are no @label yet.', ['@label' => $this->entityType->getPluralLabel()]),
      '#cache' => [
        'contexts' => $this->entityType->getListCacheContexts(),
        'tags' => $this->entityType->getListCacheTags(),
      ],
      '#attributes' => [
        'class' => ['bookkeeping-transactions'],
      ],
      '#attached' => [
        'library' => ['bookkeeping/list-builder'],
      ],
    ];
    foreach ($this->load() as $entity) {
      if ($row = $this->buildRow($entity)) {
        $build['table']['#rows'][$entity->id()] = [
          'class' => ['transaction'],
          'data' => $row,
        ];

        foreach ($entity->get('entries') as $delta => $item) {
          $item_row = $this->buildEntryRow($item);
          $build['table']['#rows']["{$entity->id()}:{$delta}"] = [
            'class' => ['entry'],
            'data' => $item_row,
          ];
        }
      }
    }

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $build['pager'] = [
        '#type' => 'pager',
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntityIds() {
    $query = $this->getStorage()->getQuery()
      ->sort('created', 'DESC');

    // Only add the pager if a limit is specified.
    if ($this->limit) {
      $query->pager($this->limit);
    }

    return $query->execute();
  }

}
