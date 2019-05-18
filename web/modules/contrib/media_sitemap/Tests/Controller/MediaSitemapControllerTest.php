<?php

/**
 * @file
 * Contains \Drupal\media_sitemap\Tests\MediaSitemapController.
 */

namespace Drupal\media_sitemap\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Core\Config\ConfigFactory;

/**
 * Provides automated tests for the media_sitemap module.
 */
class MediaSitemapControllerTest extends WebTestBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Core\Config\ConfigFactory definition.
   *
   * @var Drupal\Core\Config\ConfigFactory
   */
  protected $config_factory;
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "media_sitemap MediaSitemapController's controller functionality",
      'description' => 'Test Unit for module media_sitemap and controller MediaSitemapController.',
      'group' => 'Other',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Tests media_sitemap functionality.
   */
  public function testMediaSitemapController() {
    // Check that the basic functions of module media_sitemap.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
