<?php

namespace Drupal\metatag_cxense\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\metatag\Tests\MetatagTagsTestBase;

/**
 * Tests that each of the Metatag Facebook tags work correctly.
 *
 * @group metatag
 */
class MetatagCxenseTagsTest extends MetatagTagsTestBase {

  /**
   * {@inheritdoc}
   */
  public $tags = [
    'cxenseparse_articleid',
    'cxenseparse_pageclass',
    'cxenseparse_recs_publishtime',
  ];

  /**
   * The attribute to look for to indicate which tag.
   */
  public $test_name_attribute = 'property';

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::$modules[] = 'metatag_cxense';
    parent::setUp();
  }

  /**
   * Each of these meta tags has a different tag name vs its internal name.
   */
  public function get_test_tag_name($tag_name) {
    $tag_name = str_replace('_', ':', $tag_name);
    $tag_name = str_replace('cxenseparse', 'cXenseParse', $tag_name);
    return $tag_name;
  }

}
