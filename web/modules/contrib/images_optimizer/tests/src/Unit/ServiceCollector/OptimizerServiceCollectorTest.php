<?php

namespace Drupal\Tests\images_optimizer\Unit\ServiceCollector;

use Drupal\images_optimizer\Optimizer\OptimizerInterface;
use Drupal\images_optimizer\ServiceCollector\OptimizerServiceCollector;
use Drupal\Tests\UnitTestCase;

/**
 * Unit test class for the OptimizerServiceCollector class.
 *
 * @package Drupal\Tests\images_optimizer\Unit\ServiceCollector
 */
class OptimizerServiceCollectorTest extends UnitTestCase {

  /**
   * The optimizer service collector to test.
   *
   * @var \Drupal\images_optimizer\ServiceCollector\OptimizerServiceCollector
   */
  private $optimizerServiceCollector;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->optimizerServiceCollector = new OptimizerServiceCollector();
  }

  /**
   * Test all().
   */
  public function testAll() {
    $this->assertSame([], $this->optimizerServiceCollector->all());

    $optimizer1 = $this->createMock(OptimizerInterface::class);
    $this->optimizerServiceCollector->add($optimizer1, 'optimizer_1');
    $this->assertSame(['optimizer_1' => $optimizer1], $this->optimizerServiceCollector->all());

    $optimizer2 = $this->createMock(OptimizerInterface::class);
    $this->optimizerServiceCollector->add($optimizer2, 'optimizer_2');
    $this->assertSame([
      'optimizer_1' => $optimizer1,
      'optimizer_2' => $optimizer2,
    ], $this->optimizerServiceCollector->all());
  }

  /**
   * Test get().
   */
  public function testGet() {
    $this->assertNull($this->optimizerServiceCollector->get('optimizer'));

    $optimizer = $this->createMock(OptimizerInterface::class);
    $this->optimizerServiceCollector->add($optimizer, 'optimizer');
    $this->assertSame($optimizer, $this->optimizerServiceCollector->get('optimizer'));
  }

  /**
   * Test add() when another optimizer with the same service id is added.
   *
   * @expectedException \InvalidArgumentException
   * @expectedExceptionMessage An optimizer with the same service id has already been added.
   */
  public function testAddWhenAnotherOptimizerWithTheSameServiceIdIsAdded() {
    $this->optimizerServiceCollector->add($this->createMock(OptimizerInterface::class), 'optimizer');
    $this->optimizerServiceCollector->add($this->createMock(OptimizerInterface::class), 'optimizer');
  }

}
