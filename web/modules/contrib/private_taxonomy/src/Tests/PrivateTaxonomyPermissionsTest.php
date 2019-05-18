<?php

namespace Drupal\private_taxonomy\Tests;

/**
 * Test Private Taxonomy functionality.
 *
 * @group private_taxonomy
 */
class PrivateTaxonomyPermissionsTest extends PrivateTaxonomyTestBase {

  /**
   * Test for user with 'administer own taxonomy' permission.
   */
  public function testUserPrivateTaxonomy() {
    $admin_user = $this->drupalCreateUser(['administer taxonomy']);
    $user = $this->drupalCreateUser(['administer own taxonomy']);

    // Create a private vocabulary.
    $private = TRUE;
    $private_vocabulary = $this->createVocabulary($private);
    $private = FALSE;
    $public_vocabulary = $this->createVocabulary($private);

    // Add terms to vocabularies.
    $this->drupalLogin($user);
    $private_term = $this->createTerm($private_vocabulary);
    $this->drupalLogin($admin_user);
    $admin_term = $this->createTerm($private_vocabulary);

    $this->drupalLogin($user);

    // Test to see what vocabularies are visible.
    $this->drupalGet('admin/structure/taxonomy');
    $this->assertNoText($public_vocabulary->label(),
      t('Public vocabulary not visible.'));
    $this->assertText($private_vocabulary->label(),
      t('Private vocabulary visible.'));

    // Test to see what terms are visible.
    $this->drupalGet('admin/structure/taxonomy/manage/' .
      $private_vocabulary->id() . '/overview');
    $this->assertText($private_term->getName(), t('Private term visible.'));
    $this->assertNoText($admin_term->getName(), t('Admin term not visible.'));
  }

  /**
   * Test for user with 'view private taxonomies' permission.
   *
   * Uses 'all' for widget.
   */
  public function testViewPrivateTaxonomyAll() {
    $admin_user = $this->drupalCreateUser([
      'administer taxonomy',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer node form display',
      'bypass node access',
      'administer nodes',
      'access content overview',
    ]);
    $user = $this->drupalCreateUser([
      'view private taxonomies',
      'create page content',
      'edit own page content',
      'access content',
    ]);

    // Create a private vocabulary.
    $private = TRUE;
    $private_vocabulary = $this->createVocabulary($private);
    $private = FALSE;
    $public_vocabulary = $this->createVocabulary($private);

    // Add terms to vocabularies.
    $this->drupalLogin($user);
    $private_term = $this->createTerm($private_vocabulary);
    $this->drupalLogin($admin_user);
    $admin_term = $this->createTerm($private_vocabulary);

    $this->drupalLogin($user);

    // Test to see what vocabularies are visible.
    $this->drupalGet('admin/structure/taxonomy');
    $this->assertNoText($public_vocabulary->label(),
      t('Public vocabulary not visible.'));
    $this->assertText($private_vocabulary->label(),
      t('Private vocabulary visible.'));

    // Test to see what terms are visible.
    $this->drupalGet('admin/structure/taxonomy/manage/' .
      $private_vocabulary->id() . '/overview');
    $this->assertText($private_term->getName(), t('Private term visible.'));
    $this->assertText($admin_term->getName(), t('Admin term visible.'));

    $this->drupalLogin($admin_user);
    $edit = [
      'new_storage_type' => 'private_taxonomy_term_reference',
      'label' => 'Private',
      'field_name' => 'private',
    ];
    $this->drupalPostForm('admin/structure/types/manage/page/fields/add-field',
      $edit, t('Save and continue'));
    $edit = [
      'fields[field_private][type]' => 'options_select',
      'fields[field_private][region]' => 'content',
    ];
    $this->drupalPostForm('admin/structure/types/manage/page/form-display',
      $edit, t('Save'));
    $edit = [
      'fields[field_private][type]' => 'private_taxonomy_term_reference_link',
      'fields[field_private][region]' => 'content',
    ];
    $this->drupalPostForm('admin/structure/types/manage/page/display',
      $edit, t('Save'));
    $edit = [
      'settings[allowed_values][0][vocabulary]' => $private_vocabulary->id(),
      'settings[allowed_values][0][users]' => 'all',
    ];
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_private/storage', $edit, t('Save field settings'));

    $this->drupalGet('node/add/page');
    $this->assertText($admin_term->getName(), t('Found term'));
    $this->assertText($private_term->getName(), t('Found term'));

    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_private' => $admin_term->id(),
    ];
    $this->drupalPostForm('node/add/page', $edit, t('Save'));
    // Should find the owner's term and use it.
    $this->assertRaw('taxonomy/term/' . $admin_term->id(), t('Found term'));
    // Check taxonomy index.
    $this->drupalGet('taxonomy/term/' . $admin_term->id());
    $this->assertRaw($admin_term->getName(), t('Found term'));
  }

  /**
   * Test for user with 'view private taxonomies' permission.
   *
   * Uses 'owner' for widget.
   */
  public function testViewPrivateTaxonomyOwner() {
    $admin_user = $this->drupalCreateUser([
      'administer taxonomy',
      'administer content types',
      'administer node fields',
      'administer node display',
      'administer node form display',
    ]);
    $user = $this->drupalCreateUser([
      'view private taxonomies',
      'create page content',
      'edit own page content',
    ]);

    // Create a private vocabulary.
    $private = TRUE;
    $private_vocabulary = $this->createVocabulary($private);
    $private = FALSE;
    $public_vocabulary = $this->createVocabulary($private);

    // Add terms to vocabularies.
    $this->drupalLogin($user);
    $private_term = $this->createTerm($private_vocabulary);
    $this->drupalLogin($admin_user);
    $admin_term = $this->createTerm($private_vocabulary);

    $this->drupalLogin($user);

    // Test to see what vocabularies are visible.
    $this->drupalGet('admin/structure/taxonomy');
    $this->assertNoText($public_vocabulary->label(),
      t('Public vocabulary not visible.'));
    $this->assertText($private_vocabulary->label(),
      t('Private vocabulary visible.'));

    // Test to see what terms are visible.
    $this->drupalGet('admin/structure/taxonomy/manage/' .
      $private_vocabulary->id() . '/overview');
    $this->assertText($private_term->getName(), t('Private term visible.'));
    $this->assertText($admin_term->getName(), t('Admin term visible.'));

    $this->drupalLogin($admin_user);
    $edit = [
      'new_storage_type' => 'private_taxonomy_term_reference',
      'label' => 'Private',
      'field_name' => 'private',
    ];
    $this->drupalPostForm('admin/structure/types/manage/page/fields/add-field',
      $edit, t('Save and continue'));
    $edit = [
      'fields[field_private][type]' => 'options_select',
      'fields[field_private][region]' => 'content',
    ];
    $this->drupalPostForm('admin/structure/types/manage/page/form-display',
      $edit, t('Save'));
    $edit = [
      'fields[field_private][type]' => 'private_taxonomy_term_reference_plain',
      'fields[field_private][region]' => 'content',
    ];
    $this->drupalPostForm('admin/structure/types/manage/page/display',
      $edit, t('Save'));
    $edit = [
      'settings[allowed_values][0][vocabulary]' => $private_vocabulary->id(),
      'settings[allowed_values][0][users]' => 'owner',
    ];
    $this->drupalPostForm('admin/structure/types/manage/page/fields/node.page.field_private/storage', $edit, t('Save field settings'));

    $this->drupalLogin($user);
    $this->drupalGet('node/add/page');
    $this->assertNoText($admin_term->getName(), t('Term not found'));
    $this->assertText($private_term->getName(), t('Found term'));

    $edit = [
      'title[0][value]' => $this->randomMachineName(),
      'field_private' => $private_term->id(),
    ];
    $this->drupalPostForm('node/add/page', $edit, t('Save'));

    // Should find the owner's term and use it.
    $this->assertText($private_term->getName(), t('Found term'));
    // Check taxonomy index.
    $this->drupalGet('taxonomy/term/' . $private_term->id());
    $this->assertRaw($private_term->getName(), t('Found term'));
  }

  /**
   * Test for user with both permissions.
   */
  public function testBothPrivateTaxonomy() {
    $admin_user = $this->drupalCreateUser(['administer taxonomy']);
    $user = $this->drupalCreateUser([
      'administer own taxonomy',
      'view private taxonomies',
    ]);

    // Create a private vocabulary.
    $private = TRUE;
    $private_vocabulary = $this->createVocabulary($private);
    $private = FALSE;
    $public_vocabulary = $this->createVocabulary($private);

    // Add terms to vocabularies.
    $this->drupalLogin($user);
    $private_term = $this->createTerm($private_vocabulary);
    $this->drupalLogin($admin_user);
    $admin_term = $this->createTerm($private_vocabulary);

    $this->drupalLogin($user);

    // Test to see what vocabularies are visible.
    $this->drupalGet('admin/structure/taxonomy');
    $this->assertNoText($public_vocabulary->label(),
      t('Public vocabulary not visible.'));
    $this->assertText($private_vocabulary->label(),
      t('Private vocabulary visible.'));

    // Test to see what terms are visible.
    $this->drupalGet('admin/structure/taxonomy/manage/' .
      $private_vocabulary->id() . '/overview');
    $this->assertText($private_term->getName(), t('Private term visible.'));
    $this->assertText($admin_term->getName(), t('Admin term visible.'));
  }

}
