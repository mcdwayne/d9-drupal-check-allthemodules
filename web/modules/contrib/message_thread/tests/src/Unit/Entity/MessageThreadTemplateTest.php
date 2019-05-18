<?php

namespace Drupal\Tests\message_thread\Unit\Entity;

use Drupal\message_thread\Entity\MessageThreadTemplate;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for the message template entity.
 *
 * @coversDefaultClass \Drupal\message_thread\Entity\MessageThreadTemplate
 *
 * @group message_thread
 */
class MessageThreadTemplateTest extends UnitTestCase {

  /**
   * A message template entity.
   *
   * @var \Drupal\message\MessageTemplateInterface
   */
  protected $messageThreadTemplate;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->messageThreadTemplate = new MessageThreadTemplate(['template' => 'foo_template'], 'message_thread_template');
  }

  /**
   * Test the ID method.
   *
   * @covers ::id
   */
  public function testId() {
    $this->assertSame('foo_template', $this->messageThreadTemplate->id());
  }

  /**
   * Tests getting and setting the Settings array.
   *
   * @covers ::setSettings
   * @covers ::getSettings
   * @covers ::getSettings
   */
  public function testSetSettings() {
    $settings = [
      'one' => 'foo',
      'two' => 'bar',
    ];

    $this->messageThreadTemplate->setSettings($settings);
    $this->assertArrayEquals($settings, $this->messageThreadTemplate->getSettings());
    $this->assertEquals($this->messageThreadTemplate->getSetting('one'), $this->messageThreadTemplate->getSetting('one'));
    $this->assertEquals('bar', $this->messageThreadTemplate->getSetting('two'));
  }

  /**
   * Tests getting and setting description.
   *
   * @covers ::setDescription
   * @covers ::getDescription
   */
  public function testSetDescription() {
    $description = 'A description';

    $this->messageThreadTemplate->setDescription($description);
    $this->assertEquals($description, $this->messageThreadTemplate->getDescription());
  }

  /**
   * Tests getting and setting label.
   *
   * @covers ::setLabel
   * @covers ::getLabel
   */
  public function testSetLabel() {
    $label = 'A label';
    $this->messageThreadTemplate->setLabel($label);
    $this->assertEquals($label, $this->messageThreadTemplate->getLabel());
  }

  /**
   * Tests getting and setting template.
   *
   * @covers ::setTemplate
   * @covers ::getTemplate
   */
  public function testSetTemplate() {
    $template = 'a_template';
    $this->messageThreadTemplate->setTemplate($template);
    $this->assertEquals($template, $this->messageThreadTemplate->getTemplate());
  }

  /**
   * Tests getting and setting uuid.
   *
   * @covers ::setUuid
   * @covers ::getUuid
   */
  public function testSetUuid() {
    $uuid = 'a-uuid-123';
    $this->messageThreadTemplate->setUuid($uuid);
    $this->assertEquals($uuid, $this->messageThreadTemplate->getUuid());
  }

  /**
   * Tests if the template is locked.
   *
   * @covers ::isLocked
   */
  public function testIsLocked() {
    $this->assertTrue($this->messageThreadTemplate->isLocked());
    $this->messageThreadTemplate->enforceIsNew(TRUE);
    $this->assertFalse($this->messageThreadTemplate->isLocked());
  }

}
