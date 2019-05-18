<?php

namespace Drupal\Tests\amazon_sns\Unit;

use Aws\Sns\Message;
use Drupal\amazon_sns\Event\SnsMessageEvent;
use Drupal\Tests\UnitTestCase;

/**
 * Test the SNS message event.
 *
 * @group amazon_sns
 */
class SnsMessageEventTest extends UnitTestCase {
  use PlainTextMessageTrait;

  /**
   * Test getting the SNS message from the event.
   */
  public function testGetMessage() {
    $data = json_decode($this->getFixtureBody(), TRUE);
    $message = new Message($data);
    $event = new SnsMessageEvent($message);
    $this->assertEquals($message, $event->getMessage());
  }

}
