<?php

namespace Drupal\Tests\feeds\Kernel;

use Drupal\node\Entity\Node;

/**
 * Tests for dispatching feeds events.
 *
 * @group feeds
 */
class FeedsEventsTest extends FeedsKernelTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'field',
    'node',
    'feeds',
    'text',
    'filter',
    'feeds_test_events',
  ];

  /**
   * Ensure that the prevalidate event is dispatched at the right moment.
   */
  public function testPrevalidateEvent() {
    // Create a feed type. Do not map to 'title'.
    $feed_type = $this->createFeedTypeForCsv(['guid' => 'guid'], [
      'id' => 'my_feed_type',
      'mappings' => [
        [
          'target' => 'feeds_item',
          'map' => ['guid' => 'guid'],
          'unique' => ['guid' => TRUE],
        ],
      ],
    ]);

    // Try to import a feed.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Ensure that the import failed because of validation errors.
    $messages = \Drupal::messenger()->all();
    $this->assertContains('This value should not be null.', (string) $messages['warning'][0]);
    $this->assertNodeCount(0);

    // Clear messages.
    \Drupal::messenger()->deleteAll();

    // Now create a feed type with the same settings. This time, ensure that
    // \Drupal\feeds_test_events\EventSubscriber::prevalidate() sets a title on
    // the entity, which it does only for the feed type 'no_title'.
    $feed_type = $this->createFeedTypeForCsv(['guid' => 'guid'], [
      'id' => 'no_title',
      'mappings' => [
        [
          'target' => 'feeds_item',
          'map' => ['guid' => 'guid'],
          'unique' => ['guid' => TRUE],
        ],
      ],
    ]);

    // Try to import a feed.
    $feed = $this->createFeed($feed_type->id(), [
      'source' => $this->resourcesPath() . '/csv/content.csv',
    ]);
    $feed->import();

    // Assert that there are no warnings this time.
    $messages = \Drupal::messenger()->all();
    $this->assertArrayNotHasKey('warning', $messages);
    // Assert that 2 nodes were created.
    $this->assertNodeCount(2);

    // Check title of the first created node.
    $node = Node::load(1);
    $this->assertEquals('foo', $node->getTitle());
  }

}
