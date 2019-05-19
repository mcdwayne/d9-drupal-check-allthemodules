<?php

namespace Drupal\site_map\Tests;

/**
 * Test case class for site map categories tests.
 *
 * @group site_map
 */
class SiteMapCategoriesTest extends SiteMapTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->tags = $this->getTags();
    $this->vocabulary = $this->createVocabulary();
    $this->field_tags_name = $this->createTaxonomyTermReferenceField($this->vocabulary);

    // Configure module to show categories.
    $vid = $this->vocabulary->id();
    $edit = array(
      "show_vocabularies[$vid]" => $vid,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));
  }

  /**
   * Tests category description.
   */
  public function testCategoryDescription() {
    // Assert that category description is included in the site map by default.
    $this->drupalGet('/sitemap');
    $this->assertText($this->vocabulary->description, 'Category description is included.');

    // Configure module not to show category description.
    $edit = array(
      'show_description' => FALSE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that category description is not included in the site map.
    $this->drupalGet('/sitemap');
    $this->assertNoText($this->vocabulary->description, 'Category description is not included.');
  }

  /**
   * Tests node counts by category.
   */
  public function testNodeCountsByCategory() {
    // Create dummy node.
    $title = $this->randomString();
    $edit = array(
      'title[0][value]' => $title,
      'menu[enabled]' => TRUE,
      'menu[title]' => $title,
      $this->field_tags_name => implode(',', $this->tags),
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));

    // Assert that node counts are included in the site map by default.
    $this->drupalGet('/sitemap');
    $this->assertEqual(substr_count($this->getTextContent(), '(1)'), 3, 'Node counts are included');

    // Configure module to hide node counts.
    $edit = array(
      'show_count' => FALSE,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that node counts are not included in the site map.
    $this->drupalGet('sitemap');
    $this->assertEqual(substr_count($this->getTextContent(), '(1)'), 0, 'Node counts are not included');
  }

  /**
   * Tests categories depth.
   */
  public function testCategoriesDepth() {
    $terms = $this->createTerms($this->vocabulary);
    $tags = array();

    // Get tags from terms.
    foreach ($terms as $term) {
      $tags[] = $term->label();
    }

    // Assert that no tags are listed in the site map.
    $this->drupalGet('sitemap');
    foreach ($tags as $tag) {
      $this->assertNoLink($tag);
    }

    // Create dummy node.
    $title = $this->randomString();
    $edit = array(
      'title[0][value]' => $title,
      'menu[enabled]' => TRUE,
      'menu[title]' => $title,
      $this->field_tags_name => implode(',', $tags),
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));

    // Change vocabulary depth to -1.
    $edit = array(
      'vocabulary_depth' => -1,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that all tags are listed in the site map.
    $this->drupalGet('sitemap');
    foreach ($tags as $tag) {
      $this->assertLink($tag);
    }

    // Change vocabulary depth to 0.
    $edit = array(
      'vocabulary_depth' => 0,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that no tags are listed in the site map.
    $this->drupalGet('sitemap');
    foreach ($tags as $tag) {
      $this->assertNoLink($tag);
    }

    // Change vocabulary depth to 1.
    $edit = array(
      'vocabulary_depth' => 1,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that only tag 1 is listed in the site map.
    $this->drupalGet('sitemap');
    $this->assertLink($tags[0]);
    $this->assertNoLink($tags[1]);
    $this->assertNoLink($tags[2]);

    // Change vocabulary depth to 2.
    $edit = array(
      'vocabulary_depth' => 2,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that tag 1 and tag 2 are listed in the site map.
    $this->drupalGet('sitemap');
    $this->assertLink($tags[0]);
    $this->assertLink($tags[1]);
    $this->assertNoLink($tags[2]);

    // Change vocabulary depth to 3.
    $edit = array(
      'vocabulary_depth' => 3,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that all tags are listed in the site map.
    $this->drupalGet('sitemap');
    foreach ($tags as $tag) {
      $this->assertLink($tag);
    }
  }

  /**
   * Tests category count threshold.
   *
   * This test case will not pass. We need to port the patch from
   * https://www.drupal.org/node/1593570 to drupal 8.
   *
   * @see https://www.drupal.org/node/1348022
   */
  public function testCategoryCountThreshold() {
    $terms = $this->createTerms($this->vocabulary);
    $tags = array();

    // Get tags from terms.
    foreach ($terms as $term) {
      $tags[] = $term->label();
    }

    // Assert that no tags are listed in the site map.
    $this->drupalGet('sitemap');
    foreach ($tags as $tag) {
      $this->assertNoText($tag);
    }

    // Create dummy node, assign it to tag 1 and tag 3. Current structure is:
    // + tag 1 (1)
    // |-- tag 2 (0)
    // |---- tag 3 (1)
    $this->createNode(array($tags[0], $tags[2]));

    // Change category count threshold to -1.
    $edit = array(
      'term_threshold' => -1,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that all tags are listed in the site map.
    $this->drupalGet('sitemap');
    $this->assertLink($tags[0]);
    $this->assertNoLink($tags[1]);
    $this->assertText($tags[1]);
    $this->assertLink($tags[2]);

    // Change category count threshold to 0.
    $edit = array(
      'term_threshold' => 0,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that all tags are listed in the site map.
    $this->drupalGet('sitemap');
    $this->assertLink($tags[0]);
    $this->assertNoText($tags[1]);
    $this->assertNoText($tags[2]);

    // Change category count threshold to 1.
    $edit = array(
      'term_threshold' => 1,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that only tag 1 is listed in the site map.
    $this->drupalGet('sitemap');
    foreach ($tags as $tag) {
      $this->assertNoText($tag);
    }

    // Assign node to tag 2. Current structure is:
    // + tag 1 (2)
    // |-- tag 2 (1)
    // |---- tag 3 (2)
    $this->createNode($tags);

    // Assert that all tags are listed in the site map.
    $this->drupalGet('sitemap');
    $this->assertLink($tags[0]);
    $this->assertNoText($tags[1]);
    $this->assertNoText($tags[2]);

    // Change category count threshold to 2.
    $edit = array(
      'term_threshold' => 2,
    );
    $this->drupalPostForm('admin/config/search/sitemap', $edit, t('Save configuration'));

    // Assert that no tags are listed in the site map.
    $this->drupalGet('sitemap');
    foreach ($tags as $tag) {
      $this->assertNoText($tag);
    }
  }

  /**
   * Create node and assign tags to it.
   *
   * @param array $tags
   *   Tags to assign to node.
   */
  protected function createNode($tags = array()) {
    $title = $this->randomString();
    $edit = array(
      'title[0][value]' => $title,
      'menu[title]' => $title,
      $this->field_tags_name => implode(',', $tags),
    );
    $this->drupalPostForm('node/add/article', $edit, t('Save and publish'));
  }
}
