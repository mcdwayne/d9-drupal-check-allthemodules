<?php

namespace Drupal\Tests\search_365\Functional;

use Drupal\search_365\Routing\SearchViewRoute;
use Drupal\Core\Url;

/**
 * Test search block.
 *
 * @group search_365
 */
class SearchBlockTest extends Search365FunctionalTestBase {

  /**
   * Test search block form.
   */
  public function testSearchBlock() {
    $this->placeBlock('search_365_block');

    // Test redirect.
    // Go to the front page and submit the search form.
    $this->drupalGet(Url::fromRoute('<front>'));
    $terms = ['search_keys' => 'course'];
    $this->submitForm($terms, t('Search'));

    $this->assertEquals(Url::fromRoute(SearchViewRoute::ROUTE_NAME, [
      'search_query' => 'course',
    ])->setAbsolute()->toString(), $this->getSession()->getCurrentUrl());
    $assert = $this->assertSession();
    $assert->statusCodeEquals(200);
  }

}
