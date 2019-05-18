<?php

namespace Drupal\Tests\healthz\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * Base class for unit testing healthz check plugins.
 */
abstract class HealthzUnitTestBase extends UnitTestCase {

  /**
   * The plugin to test.
   *
   * @var \Drupal\healthz\Plugin\HealthzCheckInterface
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    // Add a mock string translation service to the container.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

}
