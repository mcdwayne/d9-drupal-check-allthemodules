<?php

namespace Drupal\Tests\warmer_entity\Functional;

use Drupal\Core\Queue\QueueInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Functional test for the form enqueue functionality.
 *
 * @group warmer
 */
class EnqueueFormTest extends BrowserTestBase {

  protected static $modules = ['node', 'warmer', 'warmer_entity'];

  /**
   * The admin user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  private $adminUser;

  protected function setUp() {
    parent::setUp();
    $this->adminUser = $this->createUser(['administer site configuration']);
    NodeType::create([
      'type' => 'article',
    ])->save();
  }

  /**
   * Asserts the enqueue form functionality.
   */
  public function testEnqueueForm() {
    $this->createTestContent();
    // Enable the warming of articles.
    $this->config('warmer.settings')->set('warmers', [
      'entity' => [
        'id' => 'entity',
        'frequency' => 1,
        'batchSize' => 1,
        'entity_types' => ['node:article' => 'node:article'],
      ],
    ])->save();
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute('warmer.enqueue'));
    $this->submitForm(['warmers[entity]' => true], 'Warm Caches');
    // Check the number of items being reported as enqueued.
    $elements = $this->cssSelect('div.messages');
    $element = reset($elements);
    $this->assertEquals($element->getText(), 'Status message 1 items enqueued for cache warming.');
    // Ensure there is one batch in the queue.
    $queue = \Drupal::service('queue')->get('warmer');
    assert($queue instanceof QueueInterface);
    $this->assertSame(1, $queue->numberOfItems(), 'Correct number of batches in the queue.');
  }

  /**
   * Asserts the enqueue form empty functionality.
   */
  public function testEmptyEnqueueForm() {
    $this->drupalLogin($this->adminUser);
    $this->drupalGet(Url::fromRoute('warmer.enqueue'));
    $assertions = $this->assertSession();
    $assertions->buttonExists('Warm Caches');
    // There is only one warmer in this test.
    $elements = $this->xpath('//table[@id="edit-warmers"]/tbody/tr');
    $this->assertCount(1, $elements);
    $this->click('#edit-submit');
    $elements = $this->cssSelect('div.messages');
    $element = reset($elements);
    $this->assertEquals($element->getText(), 'Status message 0 items enqueued for cache warming.');
  }

  /**
   * Creates test content for richer testing.
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  private function createTestContent() {
    Node::create([
      'type' => 'article',
      'title' => 'Test Article 1',
      'status' => NodeInterface::PUBLISHED,
    ])->save();
  }
}
