<?php

namespace Drupal\Tests\events_logging\Functional;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Simple test to ensure that main page loads with module enabled.
 *
 * @group events_logging
 */
class EventsLoggingFunctionalTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['events_logging'];

  /**
   * A user with permission to administer site configuration.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $user;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->user = $this->drupalCreateUser(['administer site configuration']);
    $this->drupalLogin($this->user);
    $conf = $this->config('events_logging.config');
    $conf->set('enabled_content_entities', ['node'])->save();
  }

  /**
   * Tests the standard events log
   */
  public function testStandardEventsLogging() {
    //create an Article Node
    $node = $this->drupalCreateNode();
    $node->title = 'Node title';
    $node->type = 'article';
    //checks that a log exists
    $query = \Drupal::entityQuery('events_logging')
      ->condition('type','node_insert')
      ->execute();
    $this->assertNotEmpty($query);
    // create a new taxonomy term (not enabled for logging)
    $term = \Drupal::entityTypeManager()
      ->getStorage('taxonomy_term')
      ->create([
        'name' => 'test_events_logging',
        'vid' => 'tags'
      ]);
    $term->save();
    $query = \Drupal::entityQuery('events_logging')
      ->condition('type','taxonomy_term_insert')
      ->execute();
    $this->assertEmpty($query);
  }

}
