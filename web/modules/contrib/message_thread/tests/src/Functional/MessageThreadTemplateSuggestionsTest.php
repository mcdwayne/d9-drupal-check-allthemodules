<?php

namespace Drupal\Tests\message_thread\Functional;

use Drupal\message_thread\Entity\MessageThread;

/**
 * Tests message template suggestions.
 *
 * @group message_thread
 */
class MessageThreadTemplateSuggestionsTest extends MessageThreadTestBase {

  /**
   * The user object.
   *
   * @var \Drupal\user\UserInterface
   */
  private $user;

  /**
   * Currently experiencing schema errors.
   *
   * @var strictConfigSchema
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->user = $this->drupalcreateuser();
  }

  /**
   * Tests if template_preprocess_message() generates the correct suggestions.
   */
  public function testMessageThemeHookSuggestions() {
    $template = 'dummy_message_thread';
    // Create message to be rendered.
    $message_thread_template = $this->createMessageThreadTemplate($template, 'Dummy message', '', ['[message_thread:author:name]']);
    $message_thread = MessageThread::create(['template' => $message_thread_template->id()])
      ->setOwner($this->user);

    $message_thread->save();
    $view_mode = 'full';

    // Simulate theming of the message.
    $build = \Drupal::entityTypeManager()->getViewBuilder('message_thread')->view($message_thread, $view_mode);

    $variables['elements'] = $build;
    $suggestions = \Drupal::moduleHandler()->invokeAll('theme_suggestions_message_thread', [$variables]);

    $expected = [
      'message_thread__full',
      'message_thread__' . $template,
      'message_thread__' . $template . '__full',
      'message_thread__' . $message_thread->id(),
      'message_thread__' . $message_thread->id() . '__full',
    ];
    $this->assertEquals($expected, $suggestions, 'Found expected message suggestions.');
  }

}
