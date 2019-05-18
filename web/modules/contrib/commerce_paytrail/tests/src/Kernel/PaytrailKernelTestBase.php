<?php

namespace Drupal\Tests\commerce_paytrail\Kernel;

use Drupal\commerce_store\StoreCreationTrait;
use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;

/**
 * Provides a base class for Paytrail kernel tests.
 */
abstract class PaytrailKernelTestBase extends EntityKernelTestBase {

  use StoreCreationTrait;

  public static $modules = [
    'address',
    'datetime',
    'entity',
    'options',
    'inline_entity_form',
    'views',
    'commerce',
    'commerce_price',
    'commerce_store',
  ];

  /**
   * The default store.
   *
   * @var \Drupal\commerce_store\Entity\StoreInterface
   */
  protected $store;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'router');
    $this->installEntitySchema('commerce_currency');
    $this->installEntitySchema('commerce_store');
    $this->installConfig(['commerce_store']);

    $currency_importer = \Drupal::service('commerce_price.currency_importer');
    $currency_importer->import('EUR');

    $this->store = $this->createStore('Default store', 'admin@example.com', 'online', TRUE, 'FI', 'EUR');
    \Drupal::entityTypeManager()->getStorage('commerce_store')->markAsDefault($this->store);
  }

}
