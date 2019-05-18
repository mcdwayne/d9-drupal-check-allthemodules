<?php

namespace Drupal\commerce_refund_log;

use CommerceGuys\Intl\Formatter\CurrencyFormatterInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the list builder for refund logs.
 */
class RefundLogEntryListBuilder extends EntityListBuilder {

  /**
   * The currency formatter.
   *
   * @var \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface
   */
  protected $currencyFormatter;

  /**
   * The date formatter.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new RefundLogEntryListBuilder object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The entity storage class.
   * @param \CommerceGuys\Intl\Formatter\CurrencyFormatterInterface $currency_formatter
   *   The currency formatter.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $date_formatter
   *   The date formatter.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityStorageInterface $storage, CurrencyFormatterInterface $currency_formatter, RouteMatchInterface $route_match, DateFormatterInterface $date_formatter) {
    parent::__construct($entity_type, $storage);

    $this->currencyFormatter = $currency_formatter;
    $this->routeMatch = $route_match;
    $this->dateFormatter = $date_formatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager')->getStorage($entity_type->id()),
      $container->get('commerce_price.currency_formatter'),
      $container->get('current_route_match'),
      $container->get('date.formatter')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'commerce_refund_log_entries';
  }

  /**
   * {@inheritdoc}
   */
  public function load() {
    $payment = $this->routeMatch->getParameter('commerce_payment');
    return $this->storage->loadMultipleByPayment($payment);
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['label'] = $this->t('Refund Amount');
    $header['refund_time'] = $this->t('Refund Time');
    $header['remote_state'] = $this->t('Remote State');
    $header['remote_id'] = $this->t('Remote ID');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\commerce_refund_log\Entity\RefundLogEntryInterface $entity */
    $amount = $entity->getAmount();
    $formatted_amount = $this->currencyFormatter->format($amount->getNumber(), $amount->getCurrencyCode());
    /** @var \Drupal\Core\Datetime\DateFormatterInterface $date_formatter */

    $row['label'] = $formatted_amount;
    $row['refund_time'] = $this->dateFormatter->format($entity->getRefundTime(), 'short');
    $row['remote_state'] = $entity->getRemoteState() ?: $this->t('N/A');
    $row['remote_id'] = $entity->getRemoteId() ?: $this->t('N/A');

    return $row + parent::buildRow($entity);
  }

}
