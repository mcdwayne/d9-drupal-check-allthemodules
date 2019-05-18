<?php

namespace Drupal\Tests\search_api_saved_searches\Functional;

use Drupal\Core\Url;
use Drupal\search_api_saved_searches\Entity\SavedSearch;
use Drupal\search_api_saved_searches\SavedSearchInterface;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;

/**
 * Tests whether saving searches works correctly with facets.
 *
 * @group search_api_saved_searches
 */
class FacetsIntegrationTest extends BrowserTestBase {

  use ExampleContentTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'search_api_saved_searches_test_facets',
  ];

  /**
   * The test index's ID.
   *
   * @var string
   */
  protected $indexId = 'database_search_index';

  /**
   * The test user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->setUpExampleStructure();
    $this->insertExampleContent();

    $this->indexItems($this->indexId);

    $this->account = $this->createUser([
      'use default search_api_saved_searches',
    ]);
    $this->drupalLogin($this->account);
  }

  /**
   * Tests whether saving searches works correctly with facets.
   */
  public function testSavedSearchWithFacets() {
    $assert_session = $this->assertSession();

    $this->drupalGet('search-api-test');
    $assert_session->pageTextContains('Type facet filters');
    $assert_session->pageTextContains('Save search');
    $assert_session->pageTextContains('article (2)');
    $this->clickLink('article');

    $assert_session->pageTextContains('Displaying 2 search results');
    $edit = [
      'label[0][value]' => 'Test saved search'
    ];
    $this->submitForm($edit, 'Save search');

    $assert_session->pageTextContains('Your saved search was successfully created.');
    $uid = $this->account->id();
    $this->drupalGet("user/$uid/saved-searches");

    $this->clickLink('Test saved search');
    $url = Url::fromUserInput('/search-api-test');
    $url->setOption('query', ['filters' => ['type:article']]);
    $url->setOption('absolute', TRUE);
    $this->assertEquals($url->toString(), $this->getSession()->getCurrentUrl());

    $searches = SavedSearch::loadMultiple();
    /** @var \Drupal\search_api_saved_searches\SavedSearchInterface $search */
    $search = reset($searches);
    $this->assertInstanceOf(SavedSearchInterface::class, $search);
    $this->assertEquals($uid, $search->getOwnerId());

    $new_results_check = \Drupal::getContainer()
      ->get('search_api_saved_searches.new_results_check');
    $new_results = $new_results_check->getNewResults($search);
    $this->assertNull($new_results);

    $this->addTestEntity(6, [
      'name' => 'Test',
      'body' => 'Test body',
      'type' => 'item',
    ]);
    $this->addTestEntity(7, [
      'name' => 'Test',
      'body' => 'Test body',
      'type' => 'article',
    ]);
    $this->indexItems($this->indexId);

    $new_results = $new_results_check->getNewResults($search);
    $this->assertEquals(1, $new_results->getResultCount());
    $items = $new_results->getResultItems();
    /** @var \Drupal\search_api\Item\ItemInterface $item */
    $item = reset($items);
    $this->assertEquals(7, $item->getOriginalObject()->getValue()->id());
  }

}
