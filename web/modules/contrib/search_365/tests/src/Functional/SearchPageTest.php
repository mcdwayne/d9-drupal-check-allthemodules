<?php

namespace Drupal\Tests\search_365\Functional;

use Drupal\search_365\Routing\SearchViewRoute;
use Drupal\Core\Url;

/**
 * Test search page behaviour.
 *
 * @group search_365
 */
class SearchPageTest extends Search365FunctionalTestBase {

  /**
   * Tests search page.
   */
  public function testSearchPage() {
    $this->drupalLogin($this->adminUser);
    // Go to settings and change the results page title.
    $settings = [
      'baseurl' => 'https://example.com',
      'collection' => 'collection1',
      'drupal_path' => 'search_365',
      'search_title' => 'Search 365 Results',
    ];
    $this->drupalGet('admin/config/search/search_365/settings');
    $this->submitForm($settings, 'Save configuration');

    // Look for success message.
    $assert = $this->assertSession();

    $assert->pageTextContains('The configuration options have been saved');

    // Go to the results page.
    $this->drupalGet('search_365');
    $assert->statusCodeEquals(200);

    // Page title is present.
    $assert->pageTextContains($settings['search_title']);

    // We should have the form.
    $assert->fieldValueEquals('edit-search-keys', '');

    // We should have the prompt.
    $assert->pageTextContains('Please enter search terms.');

    // Page title.
    $assert->pageTextContains('Search 365 Results');

    // Submit the search form.
    $terms = ['search_keys' => 'nill'];
    $this->submitForm($terms, 'Search');

    // Confirm that the user is redirected to the results page.
    $this->assertEquals(Url::fromRoute(SearchViewRoute::ROUTE_NAME, [
      'search_query' => $terms['search_keys'],
    ])->setAbsolute()->toString(), $this->getSession()->getCurrentUrl());

    // Check that we have the search query in the search keys field.
    $assert->fieldValueEquals('search_keys', $terms['search_keys']);

    // Ensure that we now have "No results found" text.
    $assert->pageTextContains('No results');
  }

}
