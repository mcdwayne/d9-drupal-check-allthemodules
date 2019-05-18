<?php

namespace Drupal\private_content\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests the private module.
 *
 * @group private
 */
class PrivateTest extends WebTestBase {

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = array('node', 'search', 'private_content');

  /**
   * Rebuild node access.
   */
  public function setUp() {
    parent::setUp();
    $this->createContentType(['type' => 'article']);
    node_access_rebuild();
  }

  /**
   * Test the "private" node access.
   *
   * - Create 3 users with "access content" and "create article" permissions.
   * - Each user creates one private and one not private article.
   * - Run cron to update search index.
   * - Test that each user can view the other user's non-private article.
   * - Test that each user cannot view the other user's private article.
   * - Test that each user finds only appropriate (non-private + own private)
   *   in search results.
   * - Create another user with 'view private content'.
   * - Test that user 4 can view all content created above.
   * - Test that user 4 can search for all content created above.
   * - Test that user 4 cannot edit private content above.
   * - Create another user with 'edit private content'
   * - Test that user 5 can edit private content.
   * - Test that user 5 can delete private content.
   * - Test listings of nodes with 'node_access' tag on database search.
   */
  function testNodeAccessBasic() {
    $num_simple_users = 3;
    $simple_users = array();

    // nodes keyed by uid and nid: $nodes[$uid][$nid] = $is_private;
    $nodes_by_user = array();
    $titles = array(); // Titles keyed by nid
    $private_nodes = array(); // Array of nids marked private.
    for ($i = 0; $i < $num_simple_users; $i++) {
      $simple_users[$i] = $this->drupalCreateUser(array('access content', 'create article content', 'search content', 'mark content as private'));
    }

    foreach ($simple_users as $web_user) {
      $this->drupalLogin($web_user);
      foreach (array(0 => 'Public', 1 => 'Private') as $is_private => $type) {
        $edit = array(
          'title[0][value]' => "$type Article created by " . $web_user->name->value,
        );
        if ($is_private) {
          $edit['private'] = TRUE;
          $edit['body[0][value]'] = 'private node';
        }
        else {
          $edit['body[0][value]'] = 'public node';
        }
        $this->drupalPostForm('node/add/article', $edit, 'Save');
        $nid = db_query('SELECT nid FROM {node_field_data} WHERE title = :title', array(':title' => $edit['title[0][value]']))->fetchField();
        debug($nid, 'getting nid');
        $node = node_load($nid);
        $this->assertEqual($is_private, $node->private->value, 'Node was properly set to private or not private in private field.');
        if ($is_private) {
          $private_nodes[] = $nid;
        }
        $titles[$nid] = $edit['title[0][value]'];
        $nodes_by_user[$web_user->id()][$nid] = $is_private;
      }
    }
    debug($nodes_by_user);
    // Build the search index.
    $this->cronRun();
    foreach ($simple_users as $web_user) {
      $this->drupalLogin($web_user);
      // Check to see that we find the number of search results expected.
      $this->checkSearchResults('Private node', 1);
      // Check own nodes to see that all are readable.
      foreach (array_keys($nodes_by_user) as $uid) {
        // All of this user's nodes should be readable to same.
        if ($uid == $web_user->id()) {
          foreach ($nodes_by_user[$uid] as $nid => $is_private) {
            $this->drupalGet('node/' . $nid);
            $this->assertResponse(200);
            $this->assertTitle($titles[$nid] . ' | Drupal', t('Correct title for node found'));
          }
        }
        else {
          // Otherwise, for other users, private nodes should get a 403,
          // but we should be able to read non-private nodes.
          foreach ($nodes_by_user[$uid] as $nid => $is_private) {
            $this->drupalGet('node/' . $nid);
            $this->assertResponse($is_private ? 403 : 200, "Node $nid by user $uid should get a " . ($is_private ? 403 : 200) . "for this user (" . $web_user->id() . ")");
            if (!$is_private) {
              $this->assertTitle($titles[$nid] . ' | Drupal', t('Correct title for node was found'));
            }
          }
        }
      }
    }

    // Now test that a user with 'access private content' can view content.
    $access_user = $this->drupalCreateUser(array('access content', 'create article content', 'access private content', 'search content'));
    $this->drupalLogin($access_user);

    // Check to see that we find the number of search results expected.
    $this->checkSearchResults('Private node', 3);

    foreach ($nodes_by_user as $uid => $private_status) {
      foreach ($private_status as $nid => $is_private) {
        $this->drupalGet('node/' . $nid);
        $this->assertResponse(200);
      }
    }

    // Test that a privileged user can edit and delete private content.
    // This test should go last, as the nodes get deleted.
    $edit_user = $this->drupalCreateUser(array('access content', 'access private content', 'edit private content', 'edit any article content', 'delete any article content'));
    $this->drupalLogin($edit_user);
    foreach ($private_nodes as $nid) {
      $body = $this->randomString(200);
      $edit = array('body[0][value]' => $body);
      $this->drupalPostForm('node/' . $nid . '/edit', $edit, 'Save');
      $this->assertText('has been updated');
      $this->drupalGet('node/' . $nid . '/delete');
      $this->drupalPostForm(NULL, array(), 'Delete');
      $this->assertText(t('has been deleted'));
    }

  }

  /**
   * On the search page, search for a string and assert the expected number
   * of results.
   *
   * @param $search_query
   *   String to search for
   * @param $expected_result_count
   *   Expected result count
   */
  function checkSearchResults($search_query, $expected_result_count) {
    $this->drupalPostForm('search/node', array('keys' => $search_query), 'Search');
    $search_results = $this->xpath("//ol[contains(@class, 'search-results')]/li");
    $this->assertEqual(count($search_results), $expected_result_count, t('Found the expected number of search results'));
  }
}
