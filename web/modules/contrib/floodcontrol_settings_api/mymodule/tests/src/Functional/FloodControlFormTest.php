<?php

namespace Drupal\Tests\mymodule\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that the form submission triggers floodcontrol.
 *
 * @group mymodule
 */
class FloodControlFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['mymodule'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Tests that the page loads with a 200 response.
   */
  public function testLoad() {
    $this->drupalGet('mymodule/form/default');
    $this->assertSession()->statusCodeEquals(200);
  }

  /**
   * Tests that the form submission triggers floodcontrol.
   */
  public function testFormSubmissions() {
    // First Submission.
    $this->drupalPostForm('mymodule/form/default', [], t('Submit'));
    $this->assertText('form_id: my_custom_form', "The form is submitted.");
    // Second Submission.
    $this->drupalPostForm('mymodule/form/default', [], t('Submit'));
    $this->assertText('form_id: my_custom_form', "The form is submitted.");
    // Third Submission.
    $this->drupalPostForm('mymodule/form/default', [], t('Submit'));
    $this->assertText('You cannot submit the form more than 2 times in 1 min. Try again later.', "The form is submitted again.");
  }

}
