<?php

namespace Drupal\mathjax\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal;

/**
 * Configuration test case for the module.
 *
 * @group MathJax
 */
class MathjaxWebTest extends WebTestBase {

  /**
   * An administrator.
   *
   * @var Drupal\user\UserInterface
   */
  protected $administrator;

  /**
   * Provide info on these tests to the admin interface.
   */
  public static function getInfo() {
    return array(
      'name' => 'MathJax tests',
      'description' => 'Tests the default configuration and admin functions.',
      'group' => 'MathJax',
    );
  }

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('mathjax', 'filter');

  /**
   * Set up the test evironment.
   */
  protected function setUp() {
    parent::setUp();

    $this->administrator = $this->drupalCreateUser(array(
      'administer mathjax',
      'administer filters',
      'access site reports',
      'access administration pages',
      'administer site configuration',
    ));
  }

  /**
   * Test the administration functions.
   */
  public function testAdmin() {
    $config = Drupal::config('mathjax.settings');
    $this->drupalLogin($this->administrator);
    $this->drupalGet('admin/config');
    $this->assertText('Configure global settings for MathJax.');
    $this->drupalGet('admin/config/content/formats/add');
    $this->assertText('Mathematics inside the configured delimiters is rendered by MathJax');
    $this->drupalGet('admin/config/content/mathjax');
    $this->assertTitle('MathJax | Drupal', 'Page title set.');
    $this->assertText('MathJax CDN URL');
    $this->assertFieldByName('cdn_url', $config->get('cdn_url'), 'Default CDN config string found.');
    $this->assertText('Enter the Mathjax CDN url here or leave it unchanged to use the one provided by www.mathjax.org.');
    $this->assertText('Configuration Type');
    $this->assertFieldByName('config_type', 0);

    $custom = '{"tex2jax":{"inlineMath":[["#","#"],["\\(","\\)"]],"processEscapes":"true"},"showProcessingMessages":"false","messageStyle":"none"}';
    $path = 'admin/config/content/mathjax';
    $edit = array(
      'config_type' => 1,
      'config_string' => $custom,
    );

    $this->drupalPostForm($path, $edit, t('Save configuration'));
    $this->assertText('Enter a JSON configuration string as documented');
    $this->assertRaw(htmlentities($custom), 'Custom configuration string found.');
  }

  /**
   * Tests the detection of MathJax libraries.
   */
  public function testLibraryDetection() {
    $this->drupalLogin($this->administrator);
    $this->drupalGet('admin/reports/status');
    $this->assertNoText('MathJax is configured to use local library files but they could not be found. See the README.');
    $this->drupalGet('admin/config/content/mathjax');
    $edit = array(
      'use_cdn' => FALSE,
    );
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->drupalGet('admin/reports/status');
    $this->assertText('MathJax is configured to use local library files but they could not be found. See the README.');
  }

  /**
   * Ensure the MathJax filter is at the bottom of the processing order.
   */
  public function testFilterOrder() {
    $this->drupalLogin($this->administrator);
    // Activate the MathJax filter on the plain_text format.
    $this->drupalGet('admin/config/content/formats/manage/plain_text');
    $edit = array('filters[filter_mathjax][status]' => TRUE);
    $this->drupalPostForm(NULL, $edit, t('Save configuration'));
    $this->drupalGet('admin/config/content/formats/manage/plain_text');
    // Ensure that MathJax appears at the bottom of the active filter list.
    $count = count($this->xpath("//div[@id='edit-filters-status']/div/input[@class='form-checkbox' and @checked='checked']"));
    $result = $this->xpath("//table[@id='filter-order']/tbody/tr[$count]/td[1]");
    $this->assertEqual($result[0]->__toString(), 'MathJax');
  }

}
