<?php

namespace Drupal\Tests\webform_confirmation_file\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Webform confiramtion file browser test.
 *
 * @group webform_browser
 */
class WebformConfirmationFileTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['webform', 'webform_confirmation_file', 'webform_confirmation_file_test'];

  /**
   * Test confirmation file handler.
   */
  public function testConfirmationFileHandler() {
    $this->drupalPostForm('/webform/test_confirmation_file', [], t('Submit'));
    $this->assertSession()->responseContains('<?xml version="1.0"?>');
  }

}
