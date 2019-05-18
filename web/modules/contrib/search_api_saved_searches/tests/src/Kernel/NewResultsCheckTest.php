<?php

namespace Drupal\Tests\search_api_saved_searches\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api_saved_searches\Entity\SavedSearch;
use Drupal\search_api_saved_searches\Entity\SavedSearchType;
use Drupal\Tests\search_api\Functional\ExampleContentTrait;
use Drupal\Tests\search_api\Kernel\TestTimeService;
use Drupal\user\Entity\User;

/**
 * Tests the functionality of "new results" checks.
 *
 * @group search_api_saved_searches
 *
 * @coversDefaultClass \Drupal\search_api_saved_searches\Service\NewResultsCheck
 */
class NewResultsCheckTest extends KernelTestBase {

  use ExampleContentTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'entity_test',
    'options',
    'search_api',
    'search_api_db',
    'search_api_saved_searches',
    'search_api_test',
    'search_api_test_db',
    'system',
    'user',
  ];

  /**
   * The search index used for testing.
   *
   * @var \Drupal\search_api\IndexInterface
   */
  protected $index;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('search_api_task');
    $this->installEntitySchema('search_api_saved_search');
    $this->installEntitySchema('entity_test_mulrev_changed');
    $this->installSchema('search_api', ['search_api_item']);
    $this->installSchema('search_api_saved_searches', ['search_api_saved_searches_old_results']);
    $this->installConfig([
      'search_api',
      'search_api_test_db',
      'search_api_saved_searches',
    ]);

    // Report all log messages as errors.
    $logger = new TestLogger('');
    $this->container->set('logger.factory', $logger);
    $this->container->set('logger.channel.search_api', $logger);
    $this->container->set('logger.channel.search_api_saved_searches', $logger);

    // Add anonymous user for having a saved search owner.
    User::create([
      'uid' => 0,
      'name' => '',
    ])->save();

    $this->setUpExampleStructure();
    $this->insertExampleContent();

    $this->index = Index::load('database_search_index');
    $this->index->indexItems();
  }

  /**
   * Tests whether new results are correctly retrieved.
   *
   * @param string|null $date_field
   *   The date field which the saved search type should be configured to use.
   * @param int[] $expected_new_results
   *   The expected new results' entity IDs.
   *
   * @dataProvider getNewResultsDataProvider
   *
   * @covers ::getNewResults
   */
  public function testGetNewResults($date_field, array $expected_new_results) {
    // Use a test time service to easily manipulate the "created" date.
    $time = new TestTimeService();
    $this->container->set('datetime.time', $time);

    if ($date_field) {
      $type = SavedSearchType::load('default');
      $options = $type->getOptions();
      $options['date_field']['database_search_index'] = $date_field;
      $type->set('options', $options);
      $type->save();
    }

    $query = $this->index->query()
      ->addCondition('type', 'article')
      ->sort('search_api_id');
    // Execute query to simulate normal workflow (and test for regressions of
    // #2955617/#2955617).
    $results = $query->execute();
    $this->assertEquals(2, $results->getResultCount());
    $this->assertEquals($this->getItemIds([4, 5]), array_keys($results->getResultItems()));

    // An item added between search execution and saving the search shouldn't
    // matter for the "Determine by result IDs" approach (since the query
    // shouldn't be re-executed).
    $this->addTestEntity(6, [
      'name' => 'test 6',
      'type' => 'article',
    ]);
    $this->index->indexItems();

    $search = SavedSearch::create([
      'type' => 'default',
      'query' => $query,
    ]);
    $search->save();

    $time->advanceTime(10);

    // Add some more test entities, one of them with the wrong type to be
    // matched (9) and one with an old "created" timestamp to confuse the "date
    // field" detection method (8).
    // @todo Remove explicit "created" values once #2903549 gets fixed.
    $this->addTestEntity(7, [
      'name' => 'test 7',
      'type' => 'article',
      'created' => $time->getRequestTime(),
    ]);
    $this->addTestEntity(8, [
      'name' => 'test 8',
      'type' => 'article',
      'created' => $time->getRequestTime() - 86400,
    ]);
    $this->addTestEntity(9, [
      'name' => 'test 9',
      'type' => 'item',
      'created' => $time->getRequestTime(),
    ]);
    $this->index->indexItems();

    $search = SavedSearch::load($search->id());
    $results = $this->container
      ->get('search_api_saved_searches.new_results_check')
      ->getNewResults($search);

    $this->assertNotNull($results);
    $this->assertEquals(count($expected_new_results), $results->getResultCount());
    $this->assertEquals($this->getItemIds($expected_new_results), array_keys($results->getResultItems()));
  }

  /**
   * Returns test datasets for testGetNewResults().
   *
   * @return array
   *   Array of argument arrays for testGetNewResults().
   */
  public function getNewResultsDataProvider() {
    return [
      'id method' => [
        NULL,
        [6, 7, 8],
      ],
      'date field method' => [
        'created',
        [7],
      ],
    ];
  }

}
