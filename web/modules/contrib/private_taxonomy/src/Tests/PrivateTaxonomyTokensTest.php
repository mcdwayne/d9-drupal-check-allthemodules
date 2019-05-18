<?php

namespace Drupal\private_taxonomy\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Test Private Taxonomy functionality.
 *
 * @group private_taxonomy
 */
class PrivateTaxonomyTokensTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['private_taxonomy', 'pathauto'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
  }

  /**
   * Settings tests.
   */
  public function testPrivateTaxonomyTokens() {
    $admin_user = $this->drupalCreateUser(['administer pathauto']);
    $this->drupalLogin($admin_user);

    $this->drupalGet('admin/config/search/path/patterns/add');
    $edit = [
      'type' => 'canonical_entities:taxonomy_term',
      'label' => 'Name',
      'id' => 'name',
    ];
    $this->drupalPostForm('admin/config/search/path/patterns/add', $edit, t('Save'));
    $edit = [
      'pattern' => '[term:vocabulary]/[term:term_owner_name]/[term:name]',
    ];
    $this->drupalPostForm('admin/config/search/path/patterns/name', $edit,
      t('Save'));
    $this->assertNoText(t('invalid tokens'), 'Token found');
    $edit = [
      'type' => 'canonical_entities:taxonomy_term',
      'label' => 'User ID',
      'id' => 'user_id',
    ];
    $this->drupalPostForm('admin/config/search/path/patterns/add', $edit, t('Save'));
    $edit = [
      'pattern' => '[term:vocabulary]/[term:term_owner_uid]/[term:name]',
    ];
    $this->drupalPostForm('admin/config/search/path/patterns/user_id', $edit,
      t('Save'));
    $this->assertNoText(t('invalid tokens'), 'Token found');
  }

}
