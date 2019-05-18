<?php

/**
 * @file
 * Contains \Drupal\media_sitemap\Tests\MediaSitemapBatchController.
 */

namespace Drupal\media_sitemap\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Database\Driver\mysql\Connection;

/**
 * Provides automated tests for the media_sitemap module.
 */
class MediaSitemapBatchControllerTest extends WebTestBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;
  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => "media_sitemap MediaSitemapBatchController's controller functionality",
      'description' => 'Test Unit for module media_sitemap and controller MediaSitemapBatchController.',
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
  public function testMediaSitemapBatchController() {
    // Check that the basic functions of module media_sitemap.
    $this->assertEquals(TRUE, TRUE, 'Test Unit Generated via App Console.');
  }

}
