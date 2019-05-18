<?php

namespace Drupal\Tests\sender\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\sender\Entity\Message;
use Drupal\sender\Plugin\SenderMessageGroup\MessageGroup;

/**
 * @coversDefaultClass \Drupal\sender\Entity\Message
 * @group sender
 */
class MessageTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sender', 'sender_test', 'node'];

  protected $entity;

  public function testSettingAndGettingLabel() {
    $this->entity->setLabel('Some cool label');
    $this->assertEquals('Some cool label', $this->entity->getLabel());
  }

  public function testSettingAndGettingGroup() {
    $this->entity->setGroupId('sender_test_user');

    $group_id = $this->entity->getGroupId();
    $group = $this->entity->getGroup();
    $this->assertEquals('sender_test_user', $group_id);
    $this->assertInstanceOf(MessageGroup::class, $group);
    $this->assertEquals('sender_test_user', $group->getId());
  }

  public function testReturnedGroupTokenTypes() {
    $this->entity->setGroupId('sender_test_user');
    $token_types = $this->entity->getTokenTypes();
    $this->assertContains('user', $token_types); // From group.
    $this->assertNotContains('node', $token_types); // From message instance.
  }

  public function testReturnedTokenTypesIncludeRecipient() {
    $token_types = $this->entity->getTokenTypes();
    $this->assertContains('sender-recipient', $token_types);
  }

  public function testSettingAndGettingTokenTypes() {
    $token_types = ['user', 'node'];
    $this->entity->setTokenTypes($token_types);
    $difference = array_diff($token_types + ['sender-recipient'], $this->entity->getTokenTypes());

    $this->assertEmpty($difference);
  }

  public function testSettingAndGettingSubject() {
    $subject = 'Some cool subject';
    $this->entity->setSubject($subject);

    $this->assertEquals($subject, $this->entity->getSubject());
  }

  public function testBodyIncludesKeys() {
    $body = $this->entity->getBody();

    $this->assertArrayHasKey('value', $body);
    $this->assertArrayHasKey('format', $body);
  }

  public function testSettingAndGettingBody() {
    $body = [
      'value' => 'Some really nice text',
      'format' => 'basic_html',
    ];

    $this->entity->setBody($body);
    $this->assertEquals($body, $this->entity->getBody());
  }

  public function testBuildingRenderArray() {
    $recipient = $this->createUser();
    $render_array = $this->entity->build($recipient);

    $this->assertEquals('sender_message', $render_array['#theme']);
    $this->assertEquals($this->entity->getSubject(), $render_array['#subject']);
    $this->assertEquals($this->entity->getBodyValue(), $render_array['#body_text']);
    $this->assertEquals($this->entity->getBodyFormat(), $render_array['#body_format']);
    $this->assertEquals($this->entity->id(), $render_array['#message_id']);
  }

  public function testTokenCleanUp() {
    // A string with tokens for the body and the subject.
    $value = '[node:nid]';

    // Sets the subject.
    $subject = $value;
    $this->entity->setSubject($subject);

    // Sets the body.
    $body = [
      'value' => $value,
      'format' => 'full_html',
    ];
    $this->entity->setBody($body);

    $recipient = $this->createUser();
    $render_array = $this->entity->build($recipient);

    $this->assertEquals('', $render_array['#subject']);
    $this->assertEquals('', $render_array['#body_text']);
  }

  public function testTokenReplacement() {
    // A string with tokens for the body and the subject.
    $value_tokens = '[sender-recipient:mail][node:title]';

    // Sets the subject.
    $subject = $value_tokens;
    $this->entity->setSubject($subject);

    // Sets the body.
    $body = [
      'value' => $value_tokens,
      'format' => 'full_html',
    ];
    $this->entity->setBody($body);

    // Creates a node to be used for token replacement.
    $values = [
      'type' => 'article',
      'title' => 'This is a node.',
    ];
    $node = Node::create($values);

    $recipient = $this->createUser();
    $render_array = $this->entity->build($recipient, ['node' => $node]);

    $value_replaced = $recipient->getEmail() . $node->getTitle();

    $this->assertEquals($value_replaced, $render_array['#subject']);
    $this->assertEquals($value_replaced, $render_array['#body_text']);
  }

  protected function setUp() {
    parent::setUp();

    // Creates a message to be tested.
    $values = [
      'id' => 'test_message',
      'subject' => 'Test message',
      'body' => [
        'value' => 'Some text',
        'format' => 'full_html',
      ],
    ];
    $this->entity = Message::create($values);
  }
}
