<?php

namespace Drupal\contact_ajax\Tests;

use Drupal\field_ui\Tests\FieldUiTestTrait;
use Drupal\simpletest\WebTestBase;

/**
 * Tests storing contact messages and viewing them through UI.
 *
 * @group contact_storage
 */
class ContactAjaxTest extends WebTestBase {

  use FieldUiTestTrait;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'node',
    'text',
    'block',
    'contact',
    'contact_test',
    'field_ui',
    'contact_ajax',
  ];

  /**
   * Tests contact messages submitted through contact form.
   */
  public function testContactAjax() {
    $this->drupalPlaceBlock('system_breadcrumb_block');
    $this->drupalPlaceBlock('local_actions_block');
    $this->drupalPlaceBlock('page_title_block');

    // Create and login administrative user.
    $admin_user = $this->drupalCreateUser([
      'access site-wide contact form',
      'administer contact forms',
      'administer users',
      'administer account settings',
      'administer contact_message fields',
      'administer contact_message form display',
      'administer contact_message display',
    ]);
    $this->drupalLogin($admin_user);

    // Test if ajax settings has been added.
    $this->drupalGet('admin/structure/contact/add');
    $this->assertText(t('Ajax Form'));

    $this->configureContactAjax();
    $this->sendContactAjax();
  }

  /**
   * Add a contact form for each confirmation_type available.
   */
  public function configureContactAjax() {

    $mail = 'simpletest@example.com';

    // Create a basic ajac contact.
    $edit = [];
    $edit['label'] = 'test_label';
    $edit['id'] = 'test_id';
    $edit['recipients'] = 'simpletest@example.com';
    $edit['reply'] = '';
    $edit['selected'] = TRUE;
    // Specific con contact_ajax.
    $edit['contact_ajax_enabled'] = TRUE;
    $edit['contact_ajax_confirmation_type'] = CONTACT_AJAX_LOAD_DEFAULT_MESSAGE;
    $this->createContactAjaxForm($edit);

    // Add a new contact form to test the custom message confirmation type
    // this form should be reload a custom text.
    $edit = [];
    $edit['id'] = 'test_custom_message_id';
    $edit['recipients'] = 'simpletest@example.com';
    $edit['label'] = 'test_label';
    $edit['recipients'] = $mail;
    $edit['reply'] = '';
    $edit['selected'] = TRUE;
    // Specific con contact_ajax.
    $edit['contact_ajax_enabled'] = TRUE;
    $edit['contact_ajax_confirmation_type'] = CONTACT_AJAX_LOAD_FROM_MESSAGE;
    $edit['contact_ajax_load_from_message[value]'] = '<div><b>test ajax message</b></div>';
    $this->createContactAjaxForm($edit);

    // Add a new contact form to test the node content confirmation type
    // this form should be reload a node content.
    $edit = [];
    $edit['id'] = 'test_node_content';
    $edit['recipients'] = 'simpletest@example.com';
    $edit['label'] = 'test_label';
    $edit['reply'] = '';
    $edit['selected'] = TRUE;
    // Specific con contact_ajax.
    $edit['contact_ajax_enabled'] = TRUE;
    $edit['contact_ajax_confirmation_type'] = CONTACT_AJAX_LOAD_FROM_URI;
    // Create a content type.
    $this->drupalCreateContentType(['type' => 'article', 'name' => 'Article']);
    $node = $this->drupalCreateNode([
      'title' => 'test ajax title',
      'type' => 'article',
    ]);
    $edit['contact_ajax_load_from_uri'] = 'test ajax title (' . $node->id() . ')';
    $this->createContactAjaxForm($edit);

    // Create a form that reload the content on another div element.
    $edit = [];
    $edit['id'] = 'test_load_other_element';
    $edit['label'] = 'test_label';
    $edit['recipients'] = $mail;
    $edit['reply'] = '';
    $edit['selected'] = TRUE;
    // Specific con contact_ajax.
    $edit['contact_ajax_enabled'] = TRUE;
    $edit['contact_ajax_confirmation_type'] = CONTACT_AJAX_LOAD_DEFAULT_MESSAGE;
    $edit['contact_ajax_prefix_id'] = 'ajax-contact-prefix';
    $edit['contact_ajax_render_selector'] = '#render-selector';
    $this->createContactAjaxForm($edit);

    // Create a form that reload the content without the form.
    $edit = [];
    $edit['id'] = 'test_load_with_validation_errors';
    $edit['label'] = 'test_label';
    $edit['recipients'] = $mail;
    $edit['reply'] = '';
    $edit['selected'] = TRUE;
    // Specific con contact_ajax.
    $edit['contact_ajax_enabled'] = TRUE;
    $edit['contact_ajax_confirmation_type'] = CONTACT_AJAX_LOAD_DEFAULT_MESSAGE;
    $this->createContactAjaxForm($edit);

    // Ensure that anonymous can submit site-wide contact form.
    user_role_grant_permissions(DRUPAL_ANONYMOUS_RID, ['access site-wide contact form']);
    $this->drupalLogout();
  }

  /**
   * Create a contact form.
   */
  public function createContactAjaxForm($edit) {
    $message = 'Your message has been sent.';
    // 8.2.x added the message field, which is by default empty. Conditionally
    // submit it if the field can be found.
    $this->drupalGet('admin/structure/contact/add');
    if ($this->xpath($this->constructFieldXpath('name', 'message'))) {
      $edit['message'] = $message;
    }
    $this->drupalPostForm('admin/structure/contact/add', $edit, t('Save'));
    $this->assertText(t('Contact form test_label has been added.'));
  }

  /**
   * Send each contact form testing respect and the confirmation_type.
   */
  public function sendContactAjax() {
    // Send form reload the same form.
    $mail = 'simpletest@example.com';
    // Submit a contact form.
    $edit = [];
    $edit['name'] = 'Test name';
    $edit['mail'] = $mail;
    $edit['subject[0][value]'] = 'test subject';
    $edit['message[0][value]'] = 'test message';

    $form_id = 'test_id';
    $this->drupalGet('contact/' . $form_id);
    $commands = $this->drupalPostAjaxForm(NULL, $edit, ['op' => t('Send message')]);
    $match = FALSE;
    foreach ($commands as $command) {
      if (isset($command['data']) && strpos($command['data'], 'Your message has been sent.') !== FALSE) {
        $match = TRUE;
      }
    }
    $this->assertTrue($match, '[OK] Your message has been sent.');

    // Submit form reload custom message.
    $form_id = 'test_custom_message_id';
    $this->drupalGet('contact/' . $form_id);
    $commands = $this->drupalPostAjaxForm(NULL, $edit, ['op' => t('Send message')]);
    $match = FALSE;
    foreach ($commands as $command) {
      if (isset($command['data']) && strpos($command['data'], 'test ajax message') !== FALSE) {
        $match = TRUE;
      }
    }
    $this->assertTrue($match, '[OK] test ajax message');

    // Send form reload another node.
    $form_id = 'test_node_content';
    $this->drupalGet('contact/' . $form_id);
    $commands = $this->drupalPostAjaxForm(NULL, $edit, ['op' => t('Send message')]);
    $match = FALSE;
    foreach ($commands as $command) {
      if (isset($command['data']) && strpos($command['data'], 'test ajax title') !== FALSE) {
        $match = TRUE;
      }
    }
    $this->assertTrue($match, '[OK] test ajax title');

    // Send form reload another node.
    $form_id = 'test_load_other_element';
    $this->drupalGet('contact/' . $form_id);
    $commands = $this->drupalPostAjaxForm(NULL, $edit, ['op' => t('Send message')]);
    $clean_old_div = FALSE;
    foreach ($commands as $command) {
      $t = (isset($command['data']) && isset($command['method']) && isset($command['selector'])
                      && $command['method'] == 'replaceWith' &&
                      $command['selector'] == '#ajax-contact-prefix' &&
                      $command['data'] == '');

      if ($t) {
        $clean_old_div = TRUE;
      }
    }
    $render_new_container = FALSE;
    foreach ($commands as $command) {
      // Then test if the replacement will be done in the new container.
      $t = (isset($command['data']) && isset($command['method']) && isset($command['selector']) && $command['method'] == 'html' &&
            $command['selector'] == '#render-selector' &&
            strpos($command['data'], 'Your message has been sent') !== FALSE);

      if ($t) {
        $render_new_container = TRUE;
      }
    }
    $this->assertTrue(($clean_old_div && $render_new_container), '[OK] test render new container');

    // Send form reload another node.
    $form_id = 'test_load_with_validation_errors';
    $this->drupalGet('contact/' . $form_id);

    $edit = [];
    $edit['name'] = '';
    $edit['mail'] = $mail;
    $edit['subject[0][value]'] = 'test subject';
    $edit['message[0][value]'] = 'test message';

    $commands = $this->drupalPostAjaxForm(NULL, $edit, ['op' => t('Send message')]);
    $match_form = FALSE;
    $match_validation_message = FALSE;
    foreach ($commands as $command) {
      if (isset($command['data']) && strpos($command['data'], '<form') !== FALSE) {
        $match_form = TRUE;
      }
      if (isset($command['data']) && strpos($command['data'], 'Your name field is required.') !== FALSE) {
        $match_validation_message = TRUE;
      }
    }
    $this->assertTrue($match_form && $match_validation_message, '[OK] test render without form');
  }

}
