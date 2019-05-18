<?php

namespace Drupal\bom\Tests;

use Drupal\bom\Entity\Component;
use Drupal\cbo_item\Tests\ItemTestBase;
use Drupal\bom\Entity\Bom;

/**
 * Provides tool functions.
 */
abstract class BomTestBase extends ItemTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['bom'];

  /**
   * A bom.
   *
   * @var \Drupal\bom\BomInterface
   */
  protected $bom;

  /**
   * A bom component.
   *
   * @var \Drupal\bom\ComponentInterface
   */
  protected $bomComponent;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->bom = $this->createBom();
    $this->bomComponent = $this->createBomComponent();
  }

  /**
   * Creates a bom based on default settings.
   */
  protected function createBom(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'number' => $this->randomMachineName(8),
      'title' => $this->randomMachineName(8),
      'item' => $this->item->id(),
    ];
    $entity = Bom::create($settings);
    $entity->save();

    return $entity;
  }

  /**
   * Create a bom component based on default settings.
   */
  protected function createBomComponent(array $settings = []) {
    // Populate defaults array.
    $settings += [
      'id' => strtolower($this->randomMachineName(8)),
      'bom' => $this->bom->id(),
      'item' => $this->item->id(),
    ];
    $entity = Component::create($settings);
    $entity->save();

    return $entity;
  }

}
