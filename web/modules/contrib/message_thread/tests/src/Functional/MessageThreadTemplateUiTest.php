<?php

namespace Drupal\Tests\message_thread\Functional;

use Drupal\message_thread\Entity\MessageThreadTemplate;

/**
 * Testing the CRUD functionality for the Message template entity.
 *
 * @group message_thread
 */
class MessageThreadTemplateUiTest extends MessageThreadTestBase {

  /**
   * Currently experiencing schema errors.
   *
   * @var strictConfigSchema
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'language',
    'config_translation',
    'message_thread',
    'filter_test',
  ];

  /**
   * The user object.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->account = $this->drupalCreateUser([
      'administer message thread templates',
      'translate configuration',
      'use text format filtered_html',
    ]);
  }

  /**
   * Test the translation interface for message thread templates.
   */
  public function testMessageThreadTemplateTranslate() {
    $this->drupalLogin($this->account);

    // Test the creation of a message thread template.
    $edit = [
      'label' => 'Dummy message thread template',
      'template' => 'dummy_message_thread_template',
      'description' => 'This is a dummy text',
    ];
    $this->drupalPostForm('admin/structure/message-thread/template/add', $edit, t('Save message thread template'));
    $this->assertText('The message thread template Dummy message thread template created successfully.', 'The message thread template was created successfully');
    $this->drupalGet('admin/structure/message-thread/manage/dummy_message_thread_template');

    $elements = [
      '//input[@value="Dummy message thread template"]' => 'The label input text exists on the page with the right text.',
      '//input[@value="This is a dummy text"]' => 'The description of the message thread exists on the page.',
    ];
    $this->verifyFormElements($elements);

    // Test the editing of a message thread template.
    $edit = [
      'label' => 'Edited dummy message',
      'description' => 'This is a dummy text after editing',
    ];
    $this->drupalPostForm('admin/structure/message-thread/manage/dummy_message_thread_template', $edit, t('Save message thread template'));

    $this->drupalGet('admin/structure/message-thread/manage/dummy_message_thread_template');

    $elements = [
      '//input[@value="Edited dummy message"]' => 'The label input text exists on the page with the right text.',
      '//input[@value="This is a dummy text after editing"]' => 'The description of the message thread exists on the page.',
    ];
    $this->verifyFormElements($elements);

    // Add language.
    // Delete message thread via the UI.
    $this->drupalPostForm('admin/structure/message-thread/delete/dummy_message_thread_template', [], 'Delete');
    $this->assertFalse(MessageThreadTemplate::load('dummy_message_thread_template'), 'The message thread template deleted via the UI successfully.');
  }

  /**
   * Verifying the form elements values in easy way.
   *
   * When all the elements are passing a pass message thread with the text "The
   * expected values is in the form." When one of the Xpath expression return
   * false the message thread will be display on screen.
   *
   * @param array $elements
   *   Array mapped by in the next format.
   *
   * @code
   *   [XPATH_EXPRESSION => MESSAGE]
   * @endcode
   */
  private function verifyFormElements(array $elements) {
    $errors = [];
    foreach ($elements as $xpath => $message) {
      $element = $this->xpath($xpath);
      if (!$element) {
        $errors[] = $message;
      }
    }

    if (empty($errors)) {
      $this->pass('All elements were found.');
    }
    else {
      $this->fail('The next errors were found: ' . implode("", $errors));
    }
  }

}
