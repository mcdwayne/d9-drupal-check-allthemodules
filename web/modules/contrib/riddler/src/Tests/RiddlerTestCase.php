<?php

/**
 * Contains Drupal\captcha\Tests\CaptchaTestCase.
 */

namespace Drupal\riddler\Tests;

use Drupal\captcha\Tests\CaptchaBaseWebTestCase;

/**
 * Tests Riddler main test case sensitivity.
 *
 * @group captcha
 */
class RiddlerTestCase extends CaptchaBaseWebTestCase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['block', 'comment', 'captcha', 'riddler'];

//  /**
//   * Testing the protection of the user log in form.
//   */
//  public function testCaptchaOnLoginForm() {
//    // Create user and test log in without CAPTCHA.
//    $user = $this->drupalCreateUser();
//    $this->drupalLogin($user);
//    // Log out again.
//    $this->drupalLogout();
//
//    // Set a CAPTCHA on login form.
//    /* @var \Drupal\captcha\Entity\CaptchaPoint $captcha_point */
//    $captcha_point = \Drupal::entityManager()->getStorage('captcha_point')->load('user_login_form');
//    $captcha_point->setCaptchaType('riddler/Riddler');
//    $captcha_point->enable()->save();
//
//    // Check if there is a CAPTCHA on the login form (look for the title).
//    $this->drupalGet('');
//    $this->assertText('Do you really hate Spam?',
//      'There should be a CAPTCHA Riddle on the form.', 'Riddler'
//    );
//
//    // Try to log in, which should fail.
//    $edit = [
//      'name' => $user->getUsername(),
//      'pass' => $user->pass_raw,
//      'captcha_response' => '?',
//    ];
//    $this->drupalPostForm(NULL, $edit, t('Log in'), [], [], self::LOGIN_HTML_FORM_ID);
//    // Check for error message.
//    $this->assertText(self::CAPTCHA_WRONG_RESPONSE_ERROR_MESSAGE, 'Riddler should block user login form', 'Riddler');
//
//    // And make sure that user is not logged in:
//    // check for name and password fields on ?q=user.
//    $this->drupalGet('user');
//    $this->assertField('name', t('Username field found.'), 'Riddler');
//    $this->assertField('pass', t('Password field found.'), 'Riddler');
//  }
//
//  /**
//   * Assert function for testing if comment posting works as it should.
//   *
//   * Creates node with comment writing enabled, tries to post comment
//   * with given CAPTCHA response (caller should enable the desired
//   * challenge on page node comment forms) and checks if
//   * the result is as expected.
//   *
//   * @param string $captcha_response
//   *   The response on the CAPTCHA.
//   * @param bool $should_pass
//   *   Describing if the posting should pass or should be blocked.
//   * @param string $message
//   *   To prefix to nested asserts.
//   */
//  protected function assertCommentPosting($captcha_response, $should_pass, $message) {
//    // Make sure comments on pages can be saved directly without preview.
//    $this->container->get('state')->set('comment_preview_page', DRUPAL_OPTIONAL);
//
//    // Create a node with comments enabled.
//    $node = $this->drupalCreateNode();
//
//    // Post comment on node.
//    $edit = $this->getCommentFormValues();
//    $comment_subject = $edit['subject[0][value]'];
//    $comment_body = $edit['comment_body[0][value]'];
//    $edit['captcha_response'] = $captcha_response;
//    $this->drupalPostForm('comment/reply/node/' . $node->id() . '/comment', $edit, t('Save'), [], [], 'comment-form');
//
//    if ($should_pass) {
//      // There should be no error message.
//      $this->assertCaptchaResponseAccepted();
//      // Get node page and check that comment shows up.
//      $this->drupalGet('node/' . $node->id());
//      $this->assertText($comment_subject, $message . ' Comment should show up on node page.', 'Riddler');
//      $this->assertText($comment_body, $message . ' Comment should show up on node page.', 'Riddler');
//    }
//    else {
//      // Check for error message.
//      $this->assertText(self::CAPTCHA_WRONG_RESPONSE_ERROR_MESSAGE, $message . ' Comment submission should be blocked.', 'Riddler');
//      // Get node page and check that comment is not present.
//      $this->drupalGet('node/' . $node->id());
//      $this->assertNoText($comment_subject, $message . ' Comment should not show up on node page.', 'Riddler');
//      $this->assertNoText($comment_body, $message . ' Comment should not show up on node page.', 'Riddler');
//    }
//  }
//
//  /**
//   * Testing the case sensitive/insensitive validation.
//   */
//  public function testCaseInsensitiveValidation() {
//    $config = $this->config('captcha.settings');
//    // Set Test CAPTCHA on comment form.
//    captcha_set_form_id_setting(self::COMMENT_FORM_ID, 'riddler/Riddler');
//
//    // Log in as normal user.
//    $this->drupalLogin($this->normalUser);
//
//    // Test case sensitive posting.
//    $config->set('default_validation', CAPTCHA_DEFAULT_VALIDATION_CASE_SENSITIVE);
//    $config->save();
//
//    $this->assertCommentPosting('Yes!', TRUE, 'Case sensitive validation of right casing.');
//    $this->assertCommentPosting('yes!', FALSE, 'Case sensitive validation of wrong casing.');
//    $this->assertCommentPosting('YES!', FALSE, 'Case sensitive validation of wrong casing.');
//
//    // Test case insensitive posting (the default).
//    $config->set('default_validation', CAPTCHA_DEFAULT_VALIDATION_CASE_INSENSITIVE);
//    $config->save();
//
//    $this->assertCommentPosting('Yes!', TRUE, 'Case insensitive validation of right casing.');
//    $this->assertCommentPosting('yes!', TRUE, 'Case insensitive validation of wrong casing.');
//    $this->assertCommentPosting('YES!', TRUE, 'Case insensitive validation of wrong casing.');
//  }

  /**
   * Drupal path of the Riddler admin page.
   */
  const RIDDLER_ADMIN_PATH = 'admin/config/people/captcha/riddler';


  /**
   * Method for testing the Riddler riddles administration.
   */
  public function testRiddlesAdministration() {
    $form_values_1_question = [
      'riddler[0][question]' => 'What is your favorite primary color?',
      'riddler[0][response]' => 'red, blue, yellow',
    ];
    $form_values_2_question = [
      'riddler[0][question]' => 'What is your favorite primary color?',
      'riddler[0][response]' => 'red, blue, yellow',
      'riddler[1][question]' => 'What color is Druplicon?',
      'riddler[1][response]' => 'blue',
    ];

    $addAnotherButton = ['op' => 'Add another riddle'];

    $this->drupalLogin($this->adminUser);

    // Check that the admin form exists.
    $this->drupalGet(self::RIDDLER_ADMIN_PATH);
    $this->assertText(t('Add questions that you require users to answer.'),
      'Admin should be able to see the Riddler admin form.', 'Riddler');

    // This creates a new set of question and response.
    $ajax = $this->drupalPostAjaxForm(self::RIDDLER_ADMIN_PATH, $form_values_1_question, $addAnotherButton);
    $this->assertText(t('Riddle 2'),
      'Admin should be able to ajax add a new riddle row.', 'Riddler');

    $this->drupalPostForm(NULL, $form_values_2_question, 'Save configuration');
    $this->assertFieldByName('riddler[1][question]', t('What color is Druplicon?'),
      'Admin should be able to add a new question.', 'Riddler');
    $this->assertFieldByName('riddler[1][response]', t('blue'),
      'Admin should be able to add a new response.', 'Riddler');

    // This deletes a set of question and response.
    $ajax = $this->drupalPostAjaxForm(self::RIDDLER_ADMIN_PATH, $form_values_2_question, 'riddle-remove-1');
    $this->assertNoFieldByName('riddler[1][question]', NULL,
      'Admin should be able to ajax remove a new question.', 'Riddler');
    $this->assertNoFieldByName('riddler[1][response]', NULL,
      'Admin should be able to ajax remove a new response.', 'Riddler');

    // Save the config form without the deleted riddle.
    $this->drupalPostForm(NULL, $form_values_1_question, 'Save configuration');
    $this->assertNoText(t('Riddle 2'),
      'Admin should be able to save without a deleted riddle.', 'Riddler');
    $this->assertNoFieldByName('riddler[1][question]', NULL,
      'Admin should be able to save to remove a new question.', 'Riddler');
    $this->assertNoFieldByName('riddler[1][response]', NULL,
      'Admin should be able to save to remove a new response.', 'Riddler');


  }

}
