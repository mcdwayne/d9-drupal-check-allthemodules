<?php

/**
 * @file
 * Contains \Drupal\Tests\retriever\Kernel\IntegrationTest.
 */

namespace Drupal\Tests\retriever\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\retriever\Fixtures\ClassWithSuggestedDependencies;

/**
 * Tests whether the finder, retriever, and factory services work.
 *
 * @group Dependency Retriever
 */
class IntegrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'retriever', 'user'];

  /**
   * Tests instantiating a class with suggested dependencies.
   */
  public function testIntegration() {
    $className = ClassWithSuggestedDependencies::class;
    /** @var \BartFeenstra\DependencyRetriever\Factory\Factory $factory */
    $factory = $this->container->get('retriever.factory');
    $instance = $factory->instantiate($className);
    $this->assertInstanceOf($className, $instance);
  }

}
