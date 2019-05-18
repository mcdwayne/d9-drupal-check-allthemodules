<?php

namespace Drupal\seo_keyword_links\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the Drupal 8 seo_keyword_links module functionality.
 *
 * @group seo_keyword_links
 */
class SeoKeywordLinksTestTestCase extends WebTestBase {

  /**
   * @var bool
   */

  protected $strictConfigSchema = FALSE;

  /**
   * @var int
   */

  protected $testNid = NULL;

  /**
   * Modules to enable.
   *
   * @var array
   */

  public static $modules = ['text', 'user', 'node', 'seo_keyword_links'];

  /**
   * Tests ...
   */
  public function testSeoKeywordLinksWorks() {
    $this->user = $this->drupalCreateUser(['access content']);
    $this->drupalLogin($this->user);

    $settings = [
      'title' => 'test page',
      'type' => 'page',
      'promote' => 1,
    ];

    $settings['body'] = [
      'value'  => 'Ono is a cable firm. Telefonica is a phone firm. Vodafone also is a phone firm.',
      'format' => 'filtered_html',
    ];

    $node = $this->drupalCreateNode($settings);
    $this->testNid = $node->id();

    $this->drupalGet("node/" . $this->testNid);
    $this->assertResponse(200);

    $this->assertRaw('<a href="http://ono.es">Ono</a>', "a link to Ono is in the test node");
    $this->assertRaw('<a href="http://telefonica.net">Telefonica</a>', "a link to Telefónica is in the test node");

  }

  /**
   * Perform any initial set up tasks that run before every test method.
   */
  public function setUp() {

    parent::setUp();

  }

}
