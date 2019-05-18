<?php

namespace Drupal\pbf\Tests;

/**
 * Test basic access without pbf fields.
 *
 * @group pbf
 */
class PbfTestBasic extends PbfTestBase {

  /**
   * Setup and Rebuild node access.
   */
  public function setUp() {
    parent::setUp();
    $this->article1 = $this->createSimpleArticle('Article 1');
    $this->article2 = $this->createSimpleArticle('Article 2');
  }

  /**
   * Test the "pbf" node access without custom permissions..
   *
   * - Test access with standard permissions.
   * - Access to each node
   * - Search the node and count result.
   */
  protected function testPbfBasicAccess() {

    $this->drupalLogin($this->normalUser);

    $this->drupalGet('node/' . $this->article1->id());
    $this->assertResponse(200);
    $this->assertTitle('Article 1 | Drupal', t('Correct title for article 1 found'));

    $this->drupalGet('node/' . $this->article2->id());
    $this->assertResponse(200);
    $this->assertTitle('Article 2 | Drupal', t('Correct title for article 2 found'));

    $this->drupalGet('node/' . $this->group1->id());
    $this->assertResponse(200);
    $this->assertTitle('Group 1 | Drupal', t('Correct title for group 1 found'));

    $this->drupalGet('node/' . $this->group2->id());
    $this->assertResponse(200);
    $this->assertTitle('Group 2 | Drupal', t('Correct title for group 2 found'));

    // Build the search index.
    $this->cronRun();
    // Check to see that we find the number of search results expected.
    $this->checkSearchResults('Article', 2);

    $this->drupalLogout();
    $this->drupalGet('node/' . $this->article1->id());
    $this->assertResponse(200);
    $this->drupalGet('node/' . $this->article2->id());
    $this->assertResponse(200);

  }

}
