<?php

namespace Drupal\supplier\Tests;

use Drupal\supplier\Entity\Supplier;
use Drupal\simpletest\WebTestBase;
use Drupal\supplier\Entity\SupplierType;

/**
 * Provides helper functions.
 */
abstract class SupplierTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['supplier'];

  /**
   * A supplier type.
   *
   * @var \Drupal\supplier\SupplierTypeInterface
   */
  protected $supplierType;

  /**
   * A supplier.
   *
   * @var \Drupal\supplier\SupplierInterface
   */
  protected $supplier;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->supplierType = $this->createSupplierType();
    $this->supplier = $this->createSupplier($this->supplierType->id());
  }

  /**
   * Creates a supplier type based on default settings.
   */
  protected function createSupplierType(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'id' => $this->randomMachineName(8),
      'label' => $this->randomMachineName(8),
    ];
    $entity = SupplierType::create($settings);
    $entity->save();

    return $entity;
  }

  /**
   * Creates a supplier based on default settings.
   */
  protected function createSupplier($type, array $settings = []) {
    // Populate defaults array.
    $settings += [
      'type' => $type,
      'title' => $this->randomMachineName(8),
      'number' => $this->randomMachineName(8),
    ];
    $entity = Supplier::create($settings);
    $entity->save();

    return $entity;
  }

}
