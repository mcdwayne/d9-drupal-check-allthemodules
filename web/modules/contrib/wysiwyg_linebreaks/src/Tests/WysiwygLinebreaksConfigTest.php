<?php

namespace Drupal\wysiwyg_linebreaks\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\Core\Database\Database;
use Drupal\Core\Language\Language;

/**
 * Test the functionality of the Wysiwyg Linebreaks module for an admin user.
 *
 * @group wysiwyg_linebreaks
 */
class WysiwygLinebreaksConfigTest extends WebTestBase {
  protected $admin_user;
  protected $node;

  /**
   * Use the Standard profile so default text formats are enabled.
   *
   * @var string
   */
  protected $profile = 'standard';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('wysiwyg_linebreaks');

  /**
   * Setup function.
   */
  public function setUp() {
    // Enable modules required for this test.
    parent::setUp();

    // Set up admin user.
    $this->admin_user = $this->drupalCreateUser(array(
      'administer filters',
    ));
  }

  /**
   * Test the text formats form.
   */
  public function testBasicHTMLTextFormatsConfigForm() {
    // Log in the admin user.
    $this->drupalLogin($this->admin_user);

    // Get the comment reply form and ensure there's no 'url' field.
    $this->drupalGet('admin/config/content/formats/manage/basic_html');
    $this->assertText('Force linebreaks', 'Force linebreaks conversion method shown.');
    $this->assertText('Convert linebreaks', 'Convert linebreaks conversion method shown.');
  }
}
