<?php

namespace Drupal\private_taxonomy\Tests;

/**
 * Test Private Taxonomy functionality.
 *
 * @group private_taxonomy
 */
class PrivateTaxonomyUnitTest extends PrivateTaxonomyTestBase {

  /**
   * Unit tests for the admin user.
   */
  public function testPrivateTaxonomy() {
    $admin_user = $this->drupalCreateUser(['administer taxonomy']);
    $user = $this->drupalCreateUser(['administer own taxonomy']);
    $this->drupalLogin($admin_user);

    // Create a private vocabulary.
    $private = TRUE;
    $private_vocabulary = $this->createVocabulary($private);
    $private = FALSE;
    $public_vocabulary = $this->createVocabulary($private);

    // Test to make sure the vocabulary is private.
    $this->assertEqual(TRUE,
      private_taxonomy_is_vocabulary_private($private_vocabulary->id()));
    $this->assertEqual(FALSE,
      private_taxonomy_is_vocabulary_private($public_vocabulary->id()));
    $vocabularies = private_taxonomy_get_private_vocabularies();
    $this->assertEqual(count($vocabularies), 1);
    $this->assertEqual($vocabularies[0]->label(), $private_vocabulary->label());

    // Test to see if both vocabularies are visible.
    $this->drupalGet('admin/structure/taxonomy');
    $this->assertText($public_vocabulary->label(),
      t('Public vocabulary visible.'));
    $this->assertText($private_vocabulary->label(),
      t('Private vocabulary visible.'));

    // Add terms to vocabularies.
    $this->drupalLogin($user);
    $private_term = $this->createTerm($private_vocabulary);
    $this->drupalLogin($admin_user);
    $admin_term = $this->createTerm($private_vocabulary);
    $public_term = $this->createTerm($public_vocabulary);

    // Test to make sure the term is in a private vocabulary.
    $this->assertEqual(TRUE,
      private_taxonomy_is_term_private($private_term->id()));
    $this->assertEqual(FALSE,
      private_taxonomy_is_term_private($public_term->id()));

    // Test to retrieve the owner of a term.
    $uid = private_taxonomy_term_get_user($admin_term->id());
    $this->assertEqual($admin_user->id(), $uid);
    $uid = private_taxonomy_term_get_user($private_term->id());
    $this->assertEqual($user->id(), $uid);
    $uid = private_taxonomy_term_get_user($public_term->id());
    $this->assertEqual(FALSE, $uid);

    // Test to see what terms are visible.
    $this->drupalGet('admin/structure/taxonomy/manage/' .
      $private_vocabulary->id() . '/overview');
    $this->assertText($admin_term->getName(), t('Admin private term visible.'));
    $this->assertText($private_term->getName(),
      t('User private term visible.'));
    $this->drupalGet('admin/structure/taxonomy/manage/' .
      $public_vocabulary->id() . '/overview');
    $this->assertText($public_term->getName(), t('Public term visible.'));
  }

}
