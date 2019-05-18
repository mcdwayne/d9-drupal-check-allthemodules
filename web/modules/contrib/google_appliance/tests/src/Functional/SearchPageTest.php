<?php

namespace Drupal\Tests\google_appliance\Functional;

use Drupal\google_appliance\Routing\SearchViewRoute;
use Drupal\Core\Url;

/**
 * Test search page behaviour.
 *
 * @group google_appliance
 */
class SearchPageTest extends GoogleApplianceFunctionalTestBase {

  /**
   * Tests search page.
   */
  public function testSearchPage() {
    $this->drupalLogin($this->adminUser);
    // Go to settings and change the results page title.
    $settings = [
      'hostname' => 'http://www.mygsa.net',
      'collection' => 'default_collection',
      'frontend' => 'default_frontend',
      'timeout' => 10,
      'autofilter' => '1',
      'query_inspection' => FALSE,
      'search_title' => $this->randomMachineName(16),
      'results_per_page' => 16,
    ];
    $this->drupalGet('admin/config/search/google_appliance/settings');
    $this->submitForm($settings, 'Save configuration');

    // Look for success message.
    $assert = $this->assertSession();

    $assert->pageTextContains('The configuration options have been saved');

    // Go to the results page.
    $this->drupalGet('gsearch');
    $assert->statusCodeEquals(200);
    // Page title is present.
    $assert->pageTextContains($settings['search_title']);

    // We should have the form.
    $assert->fieldValueEquals('edit-search-keys', '');

    // We should have the prompt.
    $assert->pageTextContains('Enter the terms you wish to search for.');

    // We should not have any results text ... no results error messages begin
    // with the 'Search Results' heading.
    $assert->pageTextNotContains('Search Results');

    // Submit the search form.
    $terms = ['search_keys' => $this->randomMachineName(8)];
    $this->submitForm($terms, 'Search');

    // Confirm that the user is redirected to the results page.
    $this->assertEquals(Url::fromRoute(SearchViewRoute::ROUTE_NAME, [
      'search_query' => $terms['search_keys'],
    ])->setAbsolute()->toString(), $this->getSession()->getCurrentUrl());

    // Check that we have the search query in the search keys field.
    $assert->fieldValueEquals('search_keys', $terms['search_keys']);

    // Ensure that we now have "No Results" text.
    $assert->pageTextContains('No Results');
  }

}
