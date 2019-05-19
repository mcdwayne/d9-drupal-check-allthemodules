<?php

/**
 * @file
 * Contains \Drupal\Tests\tagadelic\Unit\TagadelicTagTest.
 */

namespace Drupal\Tests\tagadelic\Unit;

use Drupal\Tests\UnitTestCase;
//use Drupal\tagadelic\TagadelicTag;

/**
 * @coversDefaultClass \Drupal\tagadelic\TagadelicTag
 * @group tagadelic
 */
class TagadelicTagTest extends UnitTestCase {

  /**
   * The TagadelicTag object being tested.
   *
   * @var \Drupal\tagadelic\TagadelicTag
   */
  protected $object;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->object = new \Drupal\tagadelic\TagadelicTag(42, "blackbeard", 2);
    $this->object->setDescription('Testing description');
  }

  /**
   * @covers ::getName
   */
  public function testGetName() {
    $this->assertEquals("blackbeard", $this->object->getName());
  }

  /**
   * @covers ::getCount
   */
  public function testGetCount() {
    $this->assertEquals(2, $this->object->getCount());
  }

  /**
   * @covers ::getId
   */
  public function testGetId() {
    $this->assertEquals(42, $this->object->getId());
  }

  /**
   * @covers ::getDescription
   */
  public function testGetDescription() {
    $this->assertEquals('Testing description', $this->object->getDescription());
  }
  
  /**
   * @covers ::getWeight
   */
  public function testGetWeight() {
    $this->assertEquals(0.0, $this->object->getWeight());
    $this->object->setWeight(1.0);
    $this->assertEquals(1.0, $this->object->getWeight());
  }
  
  /**
   * @covers ::distributed
   */
  public function testDistributed() {
    $this->assertEquals(log(2), $this->object->distributed());
  }
}
