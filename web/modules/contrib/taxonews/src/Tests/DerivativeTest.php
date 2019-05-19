<?php
namespace Drupal\taxonews\Tests;

use Drupal\taxonomy\Tests\TaxonomyTestBase;

/**
 * Tests generation of derivative blocks based on the settings form.
 */
class DerivativeTest extends TaxonomyTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('taxonomy', 'taxonews');

  /**
   * Install profile for tests.
   *
   * @var string
   */
  public $profile = 'testing';

  public static function getInfo() {
    return array(
      'name' => 'Derivative Blocks',
      'description' => 'Verifies generation and cleanup of derivative blocks.',
      'group' => 'Taxonews',
    );
  }

  function setUp() {
    parent::setUp();
    $this->admin_user = $this->drupalCreateUser(array('administer taxonomy', 'administer site configuration'));
    $this->drupalLogin($this->admin_user);
  }

  public function testConfiguration() {
    $setting = 'allowed_vocabularies';

    // Ensure default configuration.
    $config = config('taxonews.settings');
    $allowed_vocabularies = $config->get($setting);
    if (!$this->assertTrue(empty($allowed_vocabularies), t('No vocabulary enabled by default.'))) {
      debug($allowed_vocabularies, 'Allowed vocabularies');
    }
    $this->drupalGet('admin/structure/taxonomy/taxonews');
    $match = $this->xpath('//input[@type="checkbox"]');
    $this->assertTrue(empty($match), 'No vocabulary offered on settings form.');

    // Ensure new vocabularies appear.
    $vocabulary1 = $this->createVocabulary();
    $config->set($setting, array($vocabulary1->id()))->save();
    $this->drupalGet('admin/structure/taxonomy/taxonews');
    $match = $this->xpath('//input[@type="checkbox"]');
    if (!$this->assertEqual(count($match), 1, 'New vocabulary offered on settings form.')) {
      debug($match, "Incorrect number of checkboxes found");
    }
  }
}