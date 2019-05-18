<?php

namespace Drupal\Tests\agls\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Test the AGLS module.
 *
 * @group agls
 */
class AglsTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['metatag', 'agls'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $admin =  $this->createUser(['administer meta tags']);
    $this->drupalLogin($admin);
  }

  /**
   * Ensure the tags appear, without any errors.
   */
  public function testAglsBasic() {
    $this->drupalGet('/admin/config/search/metatag/add');
    /** @var \Drupal\metatag\MetatagTagPluginManager $manager */
    $manager = \Drupal::service('plugin.manager.metatag.tag');
    foreach ($manager->getDefinitions() as $id => $definition) {
      $this->assertSession()->pageTextContains($definition['label']);
    }
  }

}
