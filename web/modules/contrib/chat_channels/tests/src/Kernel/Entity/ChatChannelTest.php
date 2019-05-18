<?php

namespace Drupal\Tests\chat_channels\Kernel\Entity;

use Drupal\chat_channels\Entity\ChatChannel;
use Drupal\KernelTests\KernelTestBase;

/**
 * Class ChatChannelTest.
 *x
 * Tests getters and setters for the chat channel member entity.
 *
 * @group chat_channels
 * @coversDefaultClass \Drupal\chat_channels\Entity\ChatChannelMember
 */
class ChatChannelTest extends KernelTestBase {

  /**
   * This is required to install the module when running the test.
   *
   * @inheritdoc
   *
   */
  public static $modules = [
    'chat_channels'
  ];

  /**
   * This is required to install the module when running the test.
   *
   * @inheritdoc
   */
  protected function setUp() {
    parent::setUp();
    $this->installEntitySchema('chat_channel');
  }

  /**
   * Test the channel getters and setters.
   *
   * @covers ::setName
   * @covers ::getName
   * @covers ::setUserId
   * @covers ::getUserId
   * @covers ::setActive
   * @covers ::isActive
   * @covers ::setCreatedTime
   * @covers ::getCreatedTime
   * @covers ::setChangedTime
   * @covers ::getChangedTime
   */
  function testChannel() {
    $entity = new ChatChannel([], 'chat_channel');
    $this->assertTrue($entity instanceof ChatChannel);

    // Test set/get name.
    $name = 'test_channel';
    $entity->setName($name);
    $this->assertEquals($name, $entity->getName());

    // Test set/get UserId
    $uid = 1;
    $entity->setUserId($uid);
    $this->assertEquals($uid, $entity->getUserId());

    // Test set/get active
    $active = TRUE;
    $entity->setActive($active);
    $this->assertEquals($active, $entity->isActive());

    // Test set/get created timestamp
    $created = 1483228800;
    $entity->setCreatedTime($created);
    $this->assertEquals($active, $entity->getCreatedTime());


    // Test set/get created timestamp
    $changed = 1485907200;
    $entity->setChangedTime($changed);
    $this->assertEquals($active, $entity->getChangedTime());
  }
}