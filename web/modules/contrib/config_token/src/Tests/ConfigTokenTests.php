<?php

namespace Drupal\config_token\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Config Token tests.
 *
 * @group config_token
 */
class ConfigTokenTests extends WebTestBase {

  /**
   * The profile used during tests.
   *
   * @var string
   */
  public $profile = 'standard';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('token', 'node', 'field', 'text', 'config_token', 'token_filter');

  /**
   * Admin user.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create admin user.
    $this->adminUser = $this->drupalCreateUser(array(
      'access administration pages',
      'administer filters',
    ), 'Config Token Admin', TRUE);
    $this->drupalLogin($this->adminUser);

    // Configure plain text format.
    $this->drupalGet('admin/config/content/formats/manage/plain_text');
    $edit = [
      'filters[filter_autop][status]' => FALSE,
      'filters[filter_url][status]' => FALSE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));

    $this->drupalGet('admin/config/content/formats/manage/basic_html');
    $edit = [
      'filters[filter_url][status]' => TRUE,
      'filters[token_filter][status]' => TRUE,
    ];
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
  }

  /**
   * Check that an element exists in HTML markup.
   *
   * @param $xpath
   *   An XPath expression.
   * @param array $arguments
   *   (optional) An associative array of XPath replacement tokens to pass to
   *   DrupalWebTestCase::buildXPathQuery().
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   *
   * @return
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertElementByXPath($xpath, array $arguments = array(), $message, $group = 'Other') {
    $elements = $this->xpath($xpath, $arguments);
    return $this->assertTrue(!empty($elements[0]), $message, $group);
  }

  /**
   * Token replacements.
   */
  function testTokens() {
    $value = \Drupal::token()->replace('[config_token:example_email]', [], ['clear' => FALSE]);
    $this->assertEqual($value, 'email@example.com');

    $value = \Drupal::token()->replace('[config_token:example_phone]', [], ['clear' => FALSE]);
    $this->assertEqual($value, '02070000000');

    $value = \Drupal::token()->replace('[config_token:example_link]', [], ['clear' => FALSE]);
    $this->assertEqual($value, '<a href="http://www.example.com">http://www.example.com</a>');
  }

  /**
   * Token replacements with Token filter.
   */
  function testTokenFilter() {
    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'body[0][value]' => '[config_token:example_email]<br />[config_token:example_phone]<br />[config_token:example_link]',
      'body[0][format]' => 'basic_html',
    ];
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    $this->assertRaw('email@example.com<br />02070000000<br /><a href="http://www.example.com">http://www.example.com</a>');
  }

}
