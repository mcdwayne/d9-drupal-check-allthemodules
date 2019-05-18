<?php

/**
 * @file
 * Contains \Drupal\agcobcau\AgcobcauTest
 */

namespace Drupal\agcobcau\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\agcobcau\AgcobcauAutoloader;
use Drupal\agcobcau\Entity\NodeShed;

/**
 * Tests for agcobcau.
 *
 * @group agcobcau
 */
class AgcobcauTest extends WebTestBase {

  public static $modules = array('node', 'agcobcau');

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $storage = PhpStorageFactory::get('agcobcau');
    // Normal test runner registers early.
    if ($instance = AgcobcauAutoloader::getInstance()) {
      $instance->setStorage($storage);
    }
    else {
      // Parentless Drupal didn't register our autoloader, so do it.
      $autoloader = new AgcobcauAutoloader($storage);
      spl_autoload_register(array($autoloader, 'autoload'), TRUE, TRUE);
    }

    // Create a test node type.
    NodeType::create(array('type' => 'shed'))->save();
  }

  /**
   * Test the autoloader.
   */
  public function testAutoloader() {
    $node = NodeShed::create();
    $this->assertTrue(is_a($node, '\Drupal\node\Entity\Node'), 'Created our node');
    $this->assertIdentical($node->bundle(), 'shed');
  }

  /**
   * Test for a known property in the class.
   */
  public function testPropertyExists() {
    // Load the file and assert a property we know should exist actually does.
    $filename = PhpStorageFactory::get('agcobcau')->getFullPath('Drupal/agcobcau/Entity/NodeShed');
    $property = '@property \Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem|\Drupal\Core\Field\Plugin\Field\FieldType\IntegerItem[]|\Drupal\Core\Field\FieldItemList $nid';
    $this->assertTrue(stripos(file_get_contents($filename), $property) !== FALSE);
  }

}
