<?php

namespace Drupal\Tests\webform_scheduled_tasks\Unit;

use Drupal\Core\Entity\ContentEntityStorageInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\webform_scheduled_tasks\Iterator\WebformIteratorAggregate;

/**
 * @coversDefaultClass \Drupal\webform_scheduled_tasks\Iterator\WebformIteratorAggregate
 * @group webform_scheduled_tasks
 */
class WebformIteratorAggregateTest extends UnitTestCase {

  /**
   * @covers ::getIterator
   */
  public function testIterator() {
    // Create a range of entity IDs.
    $all_entity_ids = range(40, 57);

    // Assert with chunk sizes of 5, the entity storage will load (and for the
    // purposes of the test return) all chunks of IDs for the given range.
    $storage = $this->prophesize(ContentEntityStorageInterface::class);
    $storage->loadMultiple(range(40, 44))->willReturn(range(40, 44))->shouldBeCalled();
    $storage->loadMultiple(range(45, 49))->willReturn(range(45, 49))->shouldBeCalled();
    $storage->loadMultiple(range(50, 54))->willReturn(range(50, 54))->shouldBeCalled();
    $storage->loadMultiple(range(55, 57))->willReturn(range(55, 57))->shouldBeCalled();

    // Iterating over all the results, casting the iterator back to an array
    // should yield the entire set of IDs.
    $iterator_aggregate = new WebformIteratorAggregate($all_entity_ids, 5, $storage->reveal());
    $this->assertEquals($all_entity_ids, iterator_to_array($iterator_aggregate->getIterator()));
    $this->assertCount(18, $iterator_aggregate);
  }

}
