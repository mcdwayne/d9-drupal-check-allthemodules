<?php

namespace Drupal\Tests\amazon_sns\Unit;

use Aws\Sns\Message;
use Drupal\amazon_sns\RequestMessageValidator;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test SNS message validation.
 *
 * @group amazon_sns
 */
class RequestMessageValidatorTest extends UnitTestCase {
  use PlainTextMessageTrait;

  /**
   * Test constructing and validating a message from a Request object.
   */
  public function testGetMessageFromRequest() {
    // Note that since the message is signed, it can't be modified without
    // capturing a new message from SNS.
    $request = Request::create('http://example.com/_amazon-sns/notify', 'POST', [], [], [], $this->getFixtureServer(), $this->getFixtureBody());
    $message = RequestMessageValidator::getMessageFromRequest($request);
    $this->assertInstanceOf(Message::class, $message);
    $this->assertEquals('Unit test', $message['Message']);
  }

  /**
   * Test that validation fails when message type is missing.
   */
  public function testMissingMessageType() {
    $this->setExpectedException(\InvalidArgumentException::class);
    $server = $this->getFixtureServer();
    unset($server['HTTP_X_AMZ_SNS_MESSAGE_TYPE']);
    $request = Request::create('http://example.com/_amazon-sns/notify', 'POST', [], [], [], $server, $this->getFixtureBody());
    RequestMessageValidator::getMessageFromRequest($request);
  }

}
