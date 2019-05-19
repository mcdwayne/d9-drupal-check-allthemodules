<?php

/**
 * @file
 * Contains Drupal\textfield_confirm\Tests\EmailConfirmTest.
 */

namespace Drupal\textfield_confirm\Tests;

/**
 * Tests for the email_confirm form element.
 *
 * @group textfield_confirm
 */
class EmailConfirmTest extends TextfieldConfirmTest {

  /**
   * Tests some basic behavior tests.
   */
  public function test() {
    $this->drupalGet('form_2');
    $this->checkLabels();
    $this->checkRequiredValidation();

    // Test basic form submit.
    $this->assertPostValue('testfield', 'admin@example.com');

    // Test that the fields have to have the same value.
    $edit = ['testfield[text1]' => 'admin@example.com', 'testfield[text2]' => 'bob@example.com'];
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $this->assertUniqueText(t('The specified fields do not match.'));
    $this->assertHasClass('testfield[text1]', 'error');
    $this->assertHasClass('testfield[text2]', 'error');

    // Test that email validation works.
    $edit = ['testfield[text1]' => 'beep', 'testfield[text2]' => 'beep'];
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $this->assertUniqueText(t('The email address @email is not valid.', ['@email' => 'beep']));
    $this->assertHasClass('testfield[text1]', 'error');
    $this->assertHasClass('testfield[text2]', 'error');

    // Test that email validation and matching validation both execute at the
    // same time.
    $edit = ['testfield[text1]' => 'beep', 'testfield[text2]' => 'boop'];
    $this->drupalPostForm(NULL, $edit, t('Submit'));
    $this->assertUniqueText(t('The specified fields do not match.'));
    $this->assertHasClass('testfield[text1]', 'error');
    $this->assertHasClass('testfield[text2]', 'error');
  }

}
