<?php

namespace Drupal\Tests\search_api_saved_searches\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api_saved_searches\Entity\SavedSearch;
use Drupal\search_api_saved_searches\Entity\SavedSearchType;
use Drupal\search_api_saved_searches\Service\NewResultsCheck;
use Drupal\search_api_saved_searches\SavedSearchInterface;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\User;

/**
 * Tests CRUD functionality for saved searches.
 *
 * @group search_api_saved_searches
 * @coversDefaultClass \Drupal\search_api_saved_searches\Entity\SavedSearch
 */
class SavedSearchCrudTest extends KernelTestBase {

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'options',
    'search_api',
    'search_api_saved_searches',
    'system',
    'user',
  ];

  /**
   * A mock "new results check" service.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject|\Drupal\search_api_saved_searches\Service\NewResultsCheck
   */
  protected $newResultsCheck;

  /**
   * Log of method calls to the "new results check" service.
   *
   * @var object
   */
  protected $newResultsCheckMethodCalls;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('search_api_saved_search');
    $this->installEntitySchema('search_api_task');
    $this->installConfig('search_api_saved_searches');
    $this->installSchema('system', ['key_value_expire', 'sequences']);
    $this->installSchema('search_api_saved_searches', 'search_api_saved_searches_old_results');

    $this->newResultsCheck = $this->createMock(NewResultsCheck::class);
    // Using an object instead of an array gives us automatic pass-by-reference.
    $method_log = (object) [];
    $this->newResultsCheck->method('getNewResults')
      ->willReturnCallback(function (SavedSearchInterface $search) use ($method_log) {
        $method_log->getNewResults[] = [$search->id()];
      });
    $this->newResultsCheck->method('saveKnownResults')
      ->willReturnCallback(function (SavedSearchInterface $search) use ($method_log) {
        $method_log->saveKnownResults[] = [$search->id()];
      });
    $this->newResultsCheckMethodCalls = $method_log;
    $this->container->set('search_api_saved_searches.new_results_check', $this->newResultsCheck);
  }

  /**
   * Tests creation of a new saved search.
   *
   * @param string|null $set_label
   *   The label to set when creating the saved search.
   * @param string|array|null $keys
   *   The fulltext keywords to set on the query.
   * @param string $expected_label
   *   The expected label set on the created saved search.
   *
   * @covers ::preCreate
   * @covers ::postCreate
   *
   * @dataProvider postCreateDataProvider
   */
  public function testPostCreate($set_label, $keys, $expected_label) {
    $query = Index::create()->query();
    $query->keys($keys);

    $values = [
      'type' => 'default',
      'query' => $query,
    ];
    if ($set_label !== NULL) {
      $values['label'] = $set_label;
    }
    $search = SavedSearch::create($values);

    $this->assertEquals($expected_label, $search->label());
  }

  /**
   * Provides data for testPostCreate().
   *
   * @return array
   *   Arrays of call arguments for testPostCreate().
   *
   * @see \Drupal\Tests\search_api_saved_searches\Kernel\SavedSearchCrudTest::testPostCreate()
   */
  public function postCreateDataProvider() {
    return [
      'existing label' => [
        'Foobar',
        'lorem',
        'Foobar',
      ],
      'with keys' => [
        NULL,
        'lorem',
        'lorem',
      ],
      'without keys' => [
        NULL,
        NULL,
        'Saved search',
      ],
      'with complex keys' => [
        NULL,
        [
          '#conjunction' => 'AND',
          'foo',
          'bar',
        ],
        'Saved search',
      ],
    ];
  }

  /**
   * Tests the pre-save hook for new saved searches.
   *
   * @param int $notify_interval
   *   The notification interval to set.
   * @param int $last_executed
   *   The "Last executed" timestamp to set.
   * @param string|null $index_id
   *   The index ID to set.
   * @param int|null $expected_next_execution
   *   The expected "next_execution" field value for the saved search.
   * @param string $expected_index_id
   *   The expected "index_id" field value for the saved search.
   *
   * @covers ::preCreate
   * @covers ::preSave
   *
   * @dataProvider preSaveDataProvider
   */
  public function testPreSave($notify_interval, $last_executed, $index_id, $expected_next_execution, $expected_index_id) {
    $query = Index::create([
      'id' => 'test',
    ])->query();

    $values = [
      'type' => 'default',
      'query' => $query,
      'notify_interval' => $notify_interval,
      'last_executed' => $last_executed,
    ];
    if ($index_id !== NULL) {
      $values['index_id'] = $index_id;
    }
    $search = SavedSearch::create($values);
    $search->save();

    $this->assertNotNull($search->id());
    $this->assertEquals($expected_next_execution, $search->get('next_execution')->value);
    $this->assertEquals($expected_index_id, $search->get('index_id')->value);

    $search = SavedSearch::load($search->id());

    $this->assertEquals($expected_next_execution, $search->get('next_execution')->value);
    $this->assertEquals($expected_index_id, $search->get('index_id')->value);

    // Test that saving again leads to expected results.
    $last_executed += 10;
    if ($expected_next_execution !== NULL) {
      $expected_next_execution += 10;
    }
    $search->set('last_executed', $last_executed);
    $search->save();

    $this->assertEquals($expected_next_execution, $search->get('next_execution')->value);
  }

  /**
   * Provides data for testPreSave().
   *
   * @return array
   *   Arrays of call arguments for testPreSave().
   *
   * @see \Drupal\Tests\search_api_saved_searches\Kernel\SavedSearchCrudTest::testPreSave()
   */
  public function preSaveDataProvider() {
    return [
      'with notifications, index_id set' => [
        10,
        1234567890,
        'foobar',
        1234567890 + 10,
        'foobar',
      ],
      'with instant notifications' => [
        0,
        1234567890,
        'foobar',
        1234567890,
        'foobar',
      ],
      'without notifications, index_id not set' => [
        -1,
        1234567890,
        NULL,
        NULL,
        'test',
      ],
    ];
  }

  /**
   * Tests the post-save hook for new saved searches.
   *
   * @param bool $set_date_field
   *   Whether to set a date field to use for the saved search.
   * @param bool $set_query
   *   Whether to set a query on the saved search.
   * @param bool $expect_check
   *   Whether a call to
   *   \Drupal\search_api_saved_searches\NewResultsCheck::getNewResults() is
   *   expected.
   *
   * @covers ::preCreate
   * @covers ::postSave
   *
   * @dataProvider postSaveDataProvider
   */
  public function testPostSave($set_date_field, $set_query, $expect_check) {
    $index_id = 'test';
    if ($set_date_field) {
      $options['date_field'][$index_id] = 'created';
      SavedSearchType::load('default')->set('options', $options)->save();
    }

    $values = [
      'type' => 'default',
    ];
    if ($set_query) {
      $query = Index::create([
        'id' => $index_id,
      ])->query();
      $values['query'] = $query;
    }
    $search = SavedSearch::create($values);
    $search->save();

    $method_log = $this->newResultsCheckMethodCalls;
    if ($expect_check) {
      $this->assertEquals([[$search->id()]], $method_log->saveKnownResults);
      $this->assertObjectNotHasAttribute('getNewResults', $method_log);
    }
    else {
      $this->assertObjectNotHasAttribute('saveKnownResults', $method_log);
      $this->assertObjectNotHasAttribute('getNewResults', $method_log);
    }

    // Re-saving should never trigger a "new results" check.
    unset($method_log->getNewResults);
    $search->save();
    $this->assertObjectNotHasAttribute('getNewResults', $method_log);
  }

  /**
   * Provides data for testPostSave().
   *
   * @return array
   *   Arrays of call arguments for testPostSave().
   *
   * @see \Drupal\Tests\search_api_saved_searches\Kernel\SavedSearchCrudTest::testPostSave()
   */
  public function postSaveDataProvider() {
    return [
      'with date field' => [
        TRUE,
        TRUE,
        FALSE,
      ],
      'without query' => [
        FALSE,
        FALSE,
        FALSE,
      ],
      'expect check' => [
        FALSE,
        TRUE,
        TRUE,
      ],
    ];
  }

  /**
   * Tests the correct deletion of saved searches.
   *
   * @covers ::postDelete
   */
  public function testPostDelete() {
    $search = SavedSearch::create([
      'type' => 'default',
    ]);
    $search->save();

    \Drupal::database()->insert('search_api_saved_searches_old_results')
      ->fields([
        'search_id' => $search->id(),
        'search_type' => 'default',
        'item_id' => '1',
      ])
      ->execute();

    // Verify that the result was inserted.
    $count = \Drupal::database()->select('search_api_saved_searches_old_results', 't')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(1, $count);

    $search->delete();

    // Verify that the search can't be loaded anymore.
    $search = SavedSearch::load($search->id());
    $this->assertNull($search);

    // Verify that the saved result was deleted.
    $count = \Drupal::database()
      ->select('search_api_saved_searches_old_results', 't')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(0, $count);
  }

  /**
   * Tests the correct reaction to the deletion of a search index.
   */
  public function testIndexDelete() {
    $index_id = 'test';

    $index = Index::create([
      'id' => $index_id,
    ]);
    // Act as if the index was already saved, to make things easier.
    $index->enforceIsNew(FALSE);

    $search = SavedSearch::create([
      'type' => 'default',
      'index_id' => $index_id,
    ]);
    $search->save();

    // Verify that the search can be loaded.
    $search = SavedSearch::load($search->id());
    $this->assertNotNull($search);

    $index->delete();

    // Verify that the search was deleted.
    $search = SavedSearch::load($search->id());
    $this->assertNull($search);
  }

  /**
   * Tests whether the correct owner is set by default for a new saved search.
   */
  public function testDefaultOwner() {
    // Create the anonymous user. For that, we need the default roles.
    $anonymous = User::create([
      'uid' => 0,
      'name' => '',
    ]);
    $anonymous->save();

    // Create a saved search as anonymous.
    $values = [
      'type' => 'default',
    ];
    $search = SavedSearch::create($values);
    $owner = $search->getOwner();
    $this->assertNotNull($owner);
    $this->assertEquals(0, $owner->id());
    $this->assertEquals(0, $search->getOwnerId());

    // Log in new user.
    $user = $this->createUser();
    $uid = $user->id();
    $this->setCurrentUser($user);

    // Create a saved search as a registered user.
    $search = SavedSearch::create($values);
    $owner = $search->getOwner();
    $this->assertNotNull($owner);
    $this->assertEquals($uid, $owner->id());
    $this->assertEquals($uid, $search->getOwnerId());
  }

}
