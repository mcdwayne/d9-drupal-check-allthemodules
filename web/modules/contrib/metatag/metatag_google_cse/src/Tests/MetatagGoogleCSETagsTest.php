<?php

namespace Drupal\metatag_google_cse\Tests;

use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Google CSE tags work correctly.
 *
 * @group metatag
 */
class MetatagGoogleCSETagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  private $tags = [
    'audience',
    'department',
    'doc_status',
    'google_rating',
    'thumbnail',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_google_cse';
    parent::setUp();
  }

  /**
   * Implements {tag_name}TestTagName() for 'google_rating'.
   */
  private function googleRatingTestTagName() {
    return 'rating';
  }

}
