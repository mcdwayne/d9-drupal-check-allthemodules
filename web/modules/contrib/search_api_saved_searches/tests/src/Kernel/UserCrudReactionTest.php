<?php

namespace Drupal\Tests\search_api_saved_searches\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api\Entity\Index;
use Drupal\search_api_saved_searches\Entity\SavedSearch;
use Drupal\search_api_saved_searches\Entity\SavedSearchType;
use Drupal\user\Entity\User;

/**
 * Verifies that the module reacts correctly to user CRUD operations.
 *
 * @group search_api_saved_searches
 */
class UserCrudReactionTest extends KernelTestBase {

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
   * The test user used in these tests.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

  /**
   * Saved searches created for testing.
   *
   * @var \Drupal\search_api_saved_searches\SavedSearchInterface[]
   */
  protected $savedSearches = [];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installConfig('search_api_saved_searches');
    $this->installEntitySchema('user');
    $this->installEntitySchema('search_api_saved_search');
    $this->installEntitySchema('search_api_task');
    $this->installSchema('system', ['key_value_expire', 'sequences']);
    $this->installSchema('user', ['users_data']);
    $this->installSchema('search_api_saved_searches', 'search_api_saved_searches_old_results');

    // Add a test user that will become the owner of our saved searches.
    $this->testUser = User::create([
      'uid' => 5,
      'name' => 'test',
      'status' => TRUE,
      'mail' => 'test@example.com',
    ]);
    $this->testUser->save();

    // Add a saved search for that user.
    $this->savedSearches[] = SavedSearch::create([
      'type' => 'default',
      'uid' => $this->testUser->id(),
      'status' => TRUE,
      'notify_interval' => 3600 * 24,
      'mail' => $this->testUser->getEmail(),
    ]);

    // Add some anonymously created saved searches.
    $this->savedSearches[] = SavedSearch::create([
      'type' => 'default',
      'uid' => 0,
      'status' => TRUE,
      'notify_interval' => 3600 * 24,
      'mail' => 'foo@example.com',
    ]);
    $this->savedSearches[] = SavedSearch::create([
      'type' => 'default',
      'uid' => 0,
      'status' => TRUE,
      'notify_interval' => 3600 * 24,
      'mail' => 'foo@example.com',
    ]);
    $this->savedSearches[] = SavedSearch::create([
      'type' => 'default',
      'uid' => 0,
      'status' => TRUE,
      'notify_interval' => 3600 * 24,
      'mail' => 'bar@example.com',
    ]);
    foreach ($this->savedSearches as $search) {
      $search->save();
    }
  }

  /**
   * Verifies correct reaction to the creation of a new user.
   *
   * @see search_api_saved_searches_user_insert()
   */
  public function testUserInsert() {
    $account = User::create([
      'name' => 'foo',
      'status' => TRUE,
      'mail' => 'foo@example.com',
    ]);
    $account->save();

    // Creating a new user claimed all anonymously created alerts with the same
    // e-mail address.
    $this->reloadSavedSearches();
    $this->assertEquals($account->id(), $this->savedSearches[1]->getOwnerId());
    $this->assertEquals($account->id(), $this->savedSearches[2]->getOwnerId());
    $this->assertEquals(0, $this->savedSearches[3]->getOwnerId());

    User::create([
      'name' => 'bar',
      'status' => FALSE,
      'mail' => 'bar@example.com',
    ])->save();

    // Creating an inactive user didn't affect any alerts.
    $this->reloadSavedSearches();
    $this->assertEquals($account->id(), $this->savedSearches[1]->getOwnerId());
    $this->assertEquals($account->id(), $this->savedSearches[2]->getOwnerId());
    $this->assertEquals(0, $this->savedSearches[3]->getOwnerId());
  }

  /**
   * Verifies correct reaction to the activation of a user account.
   *
   * @see search_api_saved_searches_user_update()
   * @see _search_api_saved_searches_claim_anonymous_searches()
   */
  public function testUserActivate() {
    $account = User::create([
      'name' => 'foo',
      'status' => FALSE,
      'mail' => 'foo@example.com',
    ]);
    $account->save();

    // Creating an inactive user didn't affect any alerts.
    $this->reloadSavedSearches();
    $this->assertEquals(0, $this->savedSearches[1]->getOwnerId());
    $this->assertEquals(0, $this->savedSearches[2]->getOwnerId());
    $this->assertEquals(0, $this->savedSearches[3]->getOwnerId());

    $account->activate()->save();

    // Once activated, all anonymously created alerts with the same e-mail
    // address are moved to that user.
    $this->reloadSavedSearches();
    $this->assertEquals($account->id(), $this->savedSearches[1]->getOwnerId());
    $this->assertEquals($account->id(), $this->savedSearches[2]->getOwnerId());
    $this->assertEquals(0, $this->savedSearches[3]->getOwnerId());
  }

  /**
   * Verifies correct reaction to the deactivation of a user account.
   *
   * @see search_api_saved_searches_user_update()
   * @see _search_api_saved_searches_deactivate_searches()
   */
  public function testUserDeactivate() {
    $this->testUser->block()->save();
    $this->reloadSavedSearches();
    $search = array_shift($this->savedSearches);
    $this->assertEquals(-1, $search->get('notify_interval')->value);

    // Verify that the other alerts were unaffected.
    foreach ($this->savedSearches as $search) {
      $this->assertEquals(3600 * 24, $search->get('notify_interval')->value);
    }
  }

  /**
   * Verifies correct reaction to the deletion of a user account.
   *
   * @see search_api_saved_searches_user_delete()
   */
  public function testUserDelete() {
    $this->testUser->delete();
    $this->reloadSavedSearches();
    $search = array_shift($this->savedSearches);
    $this->assertEmpty($search);

    // Verify that other alerts were unaffected.
    $this->reloadSavedSearches();
    foreach ($this->savedSearches as $search) {
      $this->assertNotEmpty($search);
    }
  }

  /**
   * Verifies correct reaction to the deletion of a search index.
   *
   * Since the underlying function called is the same as for user deletion (and
   * there are no other reactions to index CRUD events), this is tested as part
   * of this test case.
   *
   * @see search_api_saved_searches_search_api_index_delete()
   */
  public function testIndexDelete() {
    $this->installConfig(['search_api']);
    $index = Index::create([
      'id' => 'test',
    ]);
    $index->save();

    $this->savedSearches[0]->set('index_id', 'test')->save();
    $index->delete();

    $this->reloadSavedSearches();
    $this->assertEmpty($this->savedSearches[0]);
    $this->assertNotEmpty($this->savedSearches[1]);
  }

  /**
   * Verifies correct reaction to a user changing their mail address.
   *
   * Tested on behalf of the "E-Mail" notification plugin.
   *
   * @see search_api_saved_searches_user_update()
   * @see _search_api_saved_searches_adapt_mail()
   * @see \Drupal\search_api_saved_searches\Plugin\search_api_saved_searches\notification\Email
   */
  public function testUserMailChange() {
    // Add a second saved search type that doesn't use the "E-Mail" notification
    // plugin.
    SavedSearchType::create([
      'id' => 'non_default',
      'status' => TRUE,
      'label' => 'Non-default',
    ])->save();

    // Add a saved search for the test user using a different mail address.
    $this->savedSearches[] = SavedSearch::create([
      'type' => 'default',
      'uid' => $this->testUser->id(),
      'status' => TRUE,
      'notify_interval' => 3600 * 24,
      'mail' => 'foobar@example.com',
    ]);
    end($this->savedSearches)->save();
    // Add a saved search with a different type.
    $this->savedSearches[] = SavedSearch::create([
      'type' => 'non_default',
      'uid' => $this->testUser->id(),
      'status' => TRUE,
      'notify_interval' => 3600 * 24,
    ]);
    end($this->savedSearches)->save();

    // Now change the user's e-mail address and see what happens.
    $this->testUser->setEmail('test@example.net')->save();

    $this->reloadSavedSearches();
    $this->assertEquals('test@example.net', $this->savedSearches[0]->get('mail')->value);
    $this->assertEquals('foobar@example.com', $this->savedSearches[4]->get('mail')->value);
  }

  /**
   * Reloads the saved searches in $this->savedSearches.
   */
  protected function reloadSavedSearches() {
    foreach ($this->savedSearches as $i => $search) {
      $this->savedSearches[$i] = SavedSearch::load($search->id());
    }
  }

}
