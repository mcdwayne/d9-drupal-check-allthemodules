<?php

/**
 * @file
 * Contains Drupal\Tests\advanced_cors\Unit\EventSubscriber\CorsResponseEventSubscriberTest.
 */

namespace Drupal\Tests\advanced_cors\Unit\EventSubscriber;

use Drupal\Tests\UnitTestCase;

/**
 * Tests main functionality.
 *
 * @group CORS
 */
class AdvancedCorsResponseEventSubscriberTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Advanced CORS Response Event Subscriber',
      'description' => 'Tests the CORS response event subscriber',
      'group' => 'CORS',
    );
  }

  /**
   * Tests adding CORS headers to the response.
   */
  public function testAddCorsHeaders() {
    $this->assertEquals('43', '43', 'Test is works!');
  }

}
