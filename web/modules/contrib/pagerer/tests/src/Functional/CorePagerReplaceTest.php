<?php

namespace Drupal\Tests\pagerer\Functional;

use Drupal\Tests\system\Functional\Pager\PagerTest;

/**
 * Test replacement of Drupal core pager.
 *
 * @group Pagerer
 */
class CorePagerReplaceTest extends PagerTest {

  protected $pagererAdmin = 'admin/config/user-interface/pagerer';

  public static $modules = ['dblog', 'pager_test', 'pagerer'];

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->drupalLogin($this->drupalCreateUser([
      'access site reports',
      'administer site configuration',
    ]));

    $edit = [
      'label' => 'core_replace',
      'id' => 'core_replace',
    ];
    $this->drupalPostForm($this->pagererAdmin . '/preset/add', $edit, 'Create');
    $edit = [
      'core_override_preset' => 'core_replace',
    ];
    $this->drupalPostForm($this->pagererAdmin, $edit, 'Save configuration');
  }

  /**
   * Test that pagerer specific cache tags have been added.
   */
  public function testPagerQueryParametersAndCacheContext() {
    parent::testPagerQueryParametersAndCacheContext();
    $this->assertCacheTag('config:pagerer.settings');
    $this->assertCacheTag('config:pagerer.preset.core_replace');
  }

}
