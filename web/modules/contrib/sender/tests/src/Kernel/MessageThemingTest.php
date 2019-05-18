<?php

namespace Drupal\Tests\sender\Kernel;

use Drupal\KernelTests\Core\Entity\EntityKernelTestBase;
use Drupal\sender\Entity\Message;
use Drupal\filter\Entity\FilterFormat;

/**
 * Tests declared theme hooks.
 *
 * @group sender
 */
class MessageThemingTest extends EntityKernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['sender', 'filter'];

  protected $entity;

  public function testHookTheme() {
    $info = \sender_theme([], 'module', 'sender', 'sender');

    $this->assertArrayHasKey('sender_message', $info);
    $this->assertArrayHasKey('subject', $info['sender_message']['variables']);
    $this->assertArrayHasKey('body_text', $info['sender_message']['variables']);
    $this->assertArrayHasKey('body_format', $info['sender_message']['variables']);
    $this->assertArrayHasKey('message_id', $info['sender_message']['variables']);
    $this->assertArrayHasKey('method', $info['sender_message']['variables']);
  }

  public function testHookSuggestionsWithoutMethod() {
    $message_id = $this->entity->id();

    // Builds an array of variables to pass to hook_theme_suggestions_HOOK().
    $variables = [];
    $recipient = $this->createUser();
    foreach ($this->entity->build($recipient) as $key => $value) {
      $name = substr($key, 1);
      $variables[$name] = $value;
    }
    
    $suggestions = \sender_theme_suggestions_sender_message($variables);

    $this->assertEquals(["sender_message__$message_id"], $suggestions);
  }

  public function testHookSuggestionsWithMethodSet() {
    $message_id = $this->entity->id();
    $method = 'sender_email';

    // Builds an array of variables to pass to hook_theme_suggestions_HOOK().
    $variables = [];
    $recipient = $this->createUser();
    foreach ($this->entity->build($recipient) as $key => $value) {
      $name = substr($key, 1);
      $variables[$name] = $value;
    }
    $variables['method'] = $method;

    $suggestions = \sender_theme_suggestions_sender_message($variables);

    $this->assertEquals([
      "sender_message__$method",
      "sender_message__$message_id",
      "sender_message__{$message_id}_$method",
    ], $suggestions);
  }

  public function testRenderedMessage() {
    $recipient = $this->createUser();
    $render_array = $this->entity->build($recipient);

    $renderer = \Drupal::service('renderer');
    $rendered_message = (string) $renderer->renderPlain($render_array);

    $this->assertContains($this->entity->getSubject(), $rendered_message);
    $this->assertContains($this->entity->getBodyValue(), $rendered_message);
  }

  public function testDisallowedTagsAreRemovedFromMessage() {
    $value_dangerous = '<script></script>';

    $subject = $value_dangerous;
    $this->entity->setSubject($subject);

    $body = [
      'value' => $value_dangerous,
      'format' => 'restricted_html',
    ];
    $this->entity->setBody($body);

    $recipient = $this->createUser();
    $render_array = $this->entity->build($recipient);

    $renderer = \Drupal::service('renderer');
    $rendered_message = (string) $renderer->renderPlain($render_array);

    $this->assertNotContains('<script>', $rendered_message);
  }

  protected function setUp() {
    parent::setUp();

    // The restricted HTML format is used to test if disallowed tags are removed.
    // These settings were taken from modules/filter/tests/src/Functional/FilterAdminTest.php
    $restricted_html_format = FilterFormat::create([
      'format' => 'restricted_html',
      'name' => 'Restricted HTML',
      'filters' => [
        'filter_html' => [
          'status' => TRUE,
          'weight' => -10,
          'settings' => [
            'allowed_html' => '<p> <br> <strong> <a> <em> <h4>',
          ],
        ],
        'filter_autop' => [
          'status' => TRUE,
          'weight' => 0,
        ],
        'filter_url' => [
          'status' => TRUE,
          'weight' => 0,
        ],
        'filter_htmlcorrector' => [
          'status' => TRUE,
          'weight' => 10,
        ],
      ],
    ]);
    $restricted_html_format->save();

    // Creates a message to build render arrays.
    $values = array(
      'id' => 'test_message',
      'subject' => 'Test message',
      'body' => [
        'value' => 'Some text',
        'format' => 'restricted_html',
      ],
    );
    $this->entity = Message::create($values);
  }
}
