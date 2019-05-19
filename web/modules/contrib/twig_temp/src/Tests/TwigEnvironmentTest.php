<?php

namespace Drupal\twig_temp\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\twig_temp\TwigEnvironment;
use Drupal\twig_temp\TwigTemporaryPhpStorageCache;

/**
 * Integration tests for the twig environment.
 *
 * @group twig_temp
 */
class TwigEnvironmentTest extends WebTestBase {

  public static $modules = ['twig_temp'];

  /**
   * Test that our classes are used for the twig environment.
   */
  public function testConstructEnvironment() {
    /** @var \Drupal\twig_temp\TwigEnvironment $environment */
    $environment = $this->container->get('twig');
    $this->assertTrue($environment instanceof TwigEnvironment, 'Twig environment is swapped');
    $this->assertTrue($environment->getCache() instanceof TwigTemporaryPhpStorageCache, 'Twig temporary cache is used');
  }

  /**
   * Test that we write and clear temporary storage.
   */
  public function testTemporaryStorage() {
    /** @var \Drupal\twig_temp\TwigEnvironment $environment */
    $this->drupalGet('<front>');

    $listing = scandir('temporary://twig');
    $this->assertTrue(count($listing) > 2, 'The twig temporary cache is populated');

    twig_temp_cache_flush();
    $this->assertFalse(file_exists('temporary://twig'), 'The twig temporary cache is empty after a cache flush');
  }

}
