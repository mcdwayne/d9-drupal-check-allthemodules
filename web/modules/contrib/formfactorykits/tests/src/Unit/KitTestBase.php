<?php

namespace Drupal\Tests\formfactorykits\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\formfactorykits\Services\FormFactoryKitsInterface;
use Drupal\formfactorykits\Services\FormFactoryKitsService;
use Drupal\Tests\UnitTestCase;

/**
 * Class KitTestBase
 *
 * @package Drupal\Tests\formfactorykits\Unit
 */
abstract class KitTestBase extends UnitTestCase {
  /**
   * @var FormFactoryKitsInterface
   */
  protected $k;

  /**
   * @inheritdoc
   */
  public function setUp() {
    parent::setUp();
    $container = new ContainerBuilder();
    foreach ($this->getServices() as $id => $mockService) {
      $container->set($id, $mockService);
    }
    \Drupal::setContainer($container);
    $this->k = new FormFactoryKitsService();
  }

  /**
   * @return array
   */
  public function getServices() {
    return [];
  }

  public static function assertEquals($expected, $actual, $message = '', $delta = 0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false) {
    parent::assertEquals($expected, $actual, $message, $delta, $maxDepth, $canonicalize, $ignoreCase);
  }
}
