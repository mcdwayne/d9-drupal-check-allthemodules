<?php

namespace Drupal\commerce_migrate_commerce\Plugin\migrate\source\commerce1;

use CommerceGuys\Intl\Currency\CurrencyRepository;
use Drupal\commerce_order\Entity\OrderInterface;
use Drupal\commerce_store\Resolver\DefaultStoreResolver;
use Drupal\migrate\Row;
use Drupal\migrate_drupal\Plugin\migrate\source\d7\FieldableEntity;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Gets Commerce 1 commerce_order data from database.
 *
 * @MigrateSource(
 *   id = "commerce1_order",
 *   source_module = "commerce_order"
 * )
 */
class Order extends FieldableEntity {

  /**
   * The default store resolver.
   *
   * @var \Drupal\commerce_store\Resolver\DefaultStoreResolver
   */
  protected $defaultStoreResolver;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, StateInterface $state, EntityManagerInterface $entity_manager, DefaultStoreResolver $default_store_resolver) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $state, $entity_manager);
    $this->defaultStoreResolver = $default_store_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('state'),
      $container->get('entity.manager'),
      $container->get('commerce_store.default_store_resolver')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'order_id' => t('Order ID'),
      'commerce_order_total' => t('Order Number'),
      'revision_id' => t('Revision ID'),
      'type' => t('Type'),
      'uid' => t('User ID'),
      'mail' => t('Email'),
      'status' => t('Status'),
      'created' => t('Created'),
      'changed' => t('Changed'),
      'default_store_id' => t('Default store id'),
      'refresh_state' => t('Order refresh state'),
      'hostname' => t('Hostname'),
      'data' => t('Data'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids['order_id']['type'] = 'integer';
    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    return $this->select('commerce_order', 'ord')
      ->fields('ord');
  }

  /**
   * {@inheritdoc}
   */
  public function prepareRow(Row $row) {
    // Fail early if a store does not exist on the destination.
    // Add refresh skip value to the row.
    $row->setSourceProperty('refresh_state', OrderInterface::REFRESH_SKIP);
    $default_store = $this->defaultStoreResolver->resolve();
    if ($default_store) {
      $row->setSourceProperty('default_store_id', $default_store->id());
    }
    else {
      throw new MigrateException('You must have a store saved in order to import orders.');
    }

    // Get Field API field values.
    $order_id = $row->getSourceProperty('order_id');
    $revision_id = $row->getSourceProperty('revision_id');
    foreach (array_keys($this->getFields('commerce_order', $row->getSourceProperty('type'))) as $field) {
      $row->setSourceProperty($field, $this->getFieldValues('commerce_order', $field, $order_id, $revision_id));
    }

    // Include the number of currency fraction digits in commerce_order_total.
    $currencyRepository = new CurrencyRepository();
    $value = $row->getSourceProperty('commerce_order_total');
    $currency_code = $value[0]['currency_code'];
    $value[0]['fraction_digits'] = $currencyRepository->get($currency_code)->getFractionDigits();
    $row->setSourceProperty('commerce_order_total', $value);
    $row->setSourceProperty('data', unserialize($row->getSourceProperty('data')));

    // Get shipping line items for this order.
    $query = $this->select('commerce_line_item', 'cli')
      ->fields('cli')
      ->condition('cli.order_id', $order_id)
      ->condition('cli.type', 'shipping');

    $shipping_line_items = $query->execute()->fetchAll();
    foreach ($shipping_line_items as $key => $shipping_line_item) {
      // Get Field API field values.
      $line_item_id = $shipping_line_item['line_item_id'];
      foreach (array_keys($this->getFields('commerce_line_item', 'shipping')) as $field) {
        $shipping_line_items[$key][$field] = $this->getFieldValues('commerce_line_item', $field, $line_item_id, $line_item_id);
      }
      $shipping_line_items[$key]['data'] = unserialize($shipping_line_item['data']);
      $currency_code = $shipping_line_items[$key]['commerce_total'][0]['currency_code'];
      $shipping_line_items[$key]['commerce_total'][0]['fraction_digits'] = $currencyRepository->get($currency_code)->getFractionDigits();
    }
    $row->setSourceProperty('shipping_line_items', $shipping_line_items);

  }

  /**
   * {@inheritdoc}
   */
  public function getFieldValues($entity_type, $field, $entity_id, $revision_id = NULL, $language = NULL) {
    $values = parent::getFieldValues($entity_type, $field, $entity_id, $revision_id, $language);
    // Unserialize any data blob in these fields.
    foreach ($values as $key => &$value) {
      if (isset($value['data'])) {
        $values[$key]['data'] = unserialize($value['data']);
      }
    }
    return $values;
  }

}
