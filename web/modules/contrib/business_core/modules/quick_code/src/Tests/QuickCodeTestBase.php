<?php

namespace Drupal\quick_code\Tests;

use Drupal\quick_code\Entity\QuickCode;
use Drupal\quick_code\Entity\QuickCodeType;
use Drupal\simpletest\WebTestBase;

abstract class QuickCodeTestBase extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['quick_code'];

  /**
   * A quick_code_type entity.
   *
   * @var \Drupal\quick_code\QuickCodeTypeInterface
   */
  protected $quickCodeType;

  /**
   * A quick_code entity.
   *
   * @var \Drupal\quick_code\QuickCodeInterface
   */
  protected $quickCode;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->quickCodeType = $this->createQuickCodeType();
    $this->quickCode = $this->createQuickCode();
  }

  /**
   * Creates a quick_code_type based on default settings.
   */
  protected function createQuickCodeType(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'id' => strtolower($this->randomMachineName(8)),
      'label' => $this->randomMachineName(8),
    ];
    $entity = QuickCodeType::create($settings);
    $entity->save();

    return $entity;
  }

  /**
   * Creates a quick_code based on default settings.
   */
  protected function createQuickCode(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'type' => $this->quickCodeType->id(),
      'label' => $this->randomMachineName(8),
    ];
    $entity = QuickCode::create($settings);
    $entity->save();

    return $entity;
  }

}
