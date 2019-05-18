<?php
/**
 * @file
 * Contains \Drupal\Tests\mailmute\Unit\SendStateManagerTest.
 */

namespace Drupal\Tests\mailmute\Unit;

use Drupal\Tests\UnitTestCase;

/**
 * Tests methods of the send state manager.
 *
 * @group mailmute
 * @coversDefaultClass \Drupal\mailmute\SendStateManager
 */
class SendStateManagerTest extends UnitTestCase {

  /**
   * Tests that the hierarchy is generated correctly.
   *
   * @covers ::getPluginHierarchy
   * @covers ::getPluginHierarchyLevels
   */
  function testGetPluginHierarchy() {
    // A few definition-like structures for input. Mostly follows
    // Mailmute/Inmail design but partly fake. Critical aspects are the third
    // level (child of a child) and the arbitrary order.
    $definitions = array(
      'admin_test' => ['id' => 'admin_test', 'parent_id' => 'send'],
      'really_persistent_send' => ['id' => 'really_persistent_send', 'parent_id' => 'persistent_send'],
      'temporarily_unreachable' => ['id' => 'temporarily_unreachable', 'parent_id' => 'onhold'],
      'invalid_address' => ['id' => 'invalid_address', 'parent_id' => 'onhold'],
      'send' => ['id' => 'send'],
      'persistent_sent' => ['id' => 'persistent_send', 'parent_id' => 'send'],
      'onhold' => ['id' => 'onhold'],
    );

    // Expected results.
    $hierarchy = array(
      'send' => array(
        'persistent_send' => array(
          'really_persistent_send' => array(),
        ),
        'admin_test' => array(),
      ),
      'onhold' => array(
        'invalid_address' => array(),
        'temporarily_unreachable' => array(),
      ),
    );
    $levels = array(
      'send' => 0,
      'persistent_send' => 1,
      'really_persistent_send' => 2,
      'admin_test' => 1,
      'onhold' => 0,
      'invalid_address' => 1,
      'temporarily_unreachable' => 1,
    );

    /** @var \Drupal\mailmute\SendStateManager|\PHPUnit_Framework_MockObject_MockObject $manager */
    $manager = $this->getMockBuilder('Drupal\mailmute\SendStateManager')
      ->disableOriginalConstructor()
      ->setMethods(array('getDefinitions'))
      ->getMock();
    $manager->expects($this->exactly(2))
      ->method('getDefinitions')
      ->willReturn($definitions);

    $this->assertEquals($hierarchy, $manager->getPluginHierarchy());
    $this->assertEquals($levels, $manager->getPluginHierarchyLevels());
  }

}
