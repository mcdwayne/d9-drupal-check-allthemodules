<?php

namespace Drupal\Tests\search_365\Functional;

/**
 * Tests search results are output.
 *
 * @group search_365
 */
class SearchResultsTest extends Search365FunctionalTestBase {

  /**
   * Tests search results.
   */
  public function testSearchResults() {
    $assert = $this->assertSession();
    $this->drupalGet('search/ponies');

    // Make sure we get a response, and that it is not an error message.
    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('No Results');

    // Verify that the result index of last result on the page doesn't exceed
    // the results_per_page setting.
    $page = $this->getSession()->getPage();
    $results = $page->findAll('css', 'li.search-result');
    $this->assertNotEmpty($results);
    // No more than 20 results.
    $this->assertCount(20, $results);

    // Change the results per page count to 1 to verify that we have an exact
    // match for the results per page setting.
    $config = $this->container->get('config.factory')->getEditable('search_365.settings');
    $config
      ->set('display_settings.page_size', 1)
      ->set('display_settings.search_title', 'Here are the results')
      ->save();
    // Search via URL again.
    $this->drupalGet('search/ponies');
    $results = $page->findAll('css', 'li.search-result');
    $this->assertNotEmpty($results);
    // 1 result.
    $this->assertCount(20, $results);
    $assert->pageTextContains('Here are the results');

  }

  /**
   * Tests synonyms.
   */
  public function testSynonyms() {
    // Submit the search specified in $file_spec
    // via URL (form submission already tested).
    $this->drupalGet('search/ponies');

    $assert = $this->assertSession();
    // Make sure we get a response, and that it is not an error message.
    $assert->statusCodeEquals(200);
    $assert->pageTextNotContains('No Results');

    // Test did you mean.
    $assert->pageTextContains('Did you mean');
    $assert->pageTextContains('credits');

  }

}
