<?php

namespace Drupal\Tests\sms_infobip\Unit\Plugin\SmsGateway\Infobip;

use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms_infobip\Plugin\SmsGateway\Infobip\CreditBalanceResponseHandler;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for MessageResponseHandler, XmlResponseHandler, etc.
 *
 * @group SMS Gateway
 */
class CreditsResponseHandlerTest extends UnitTestCase {

  /**
   * @dataProvider providerMessageResponseHandler
   */
  public function testHandleMethod($raw, $expected_result) {
    $handler = new CreditBalanceResponseHandler();
    $result = $handler->handle($raw);
    $this->assertEquals($expected_result, $result);
  }

  public function providerMessageResponseHandler() {
    return [
      [
        CreditsResponseHandlerTestFixtures::$testDeliveryReport1,
        (new SmsMessageResult())
          ->setCreditsBalance(47.79134),
//          'original' => [
//            'balance' => 47.79134,
//            'currency' => 'EUR',
//          ],
      ],
    ];
  }
}

class CreditsResponseHandlerTestFixtures {

  public static $testDeliveryReport1 =<<<EOF
{
  "balance": 47.79134,
  "currency": "EUR"
}
EOF;

}
