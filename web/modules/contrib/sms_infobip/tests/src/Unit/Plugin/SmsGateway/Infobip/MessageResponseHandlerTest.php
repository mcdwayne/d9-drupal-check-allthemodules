<?php

namespace Drupal\Tests\sms_infobip\Unit\Plugin\SmsGateway\Infobip;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms_infobip\Plugin\SmsGateway\Infobip\MessageResponseHandler;
use Drupal\sms_infobip\Tests\Plugin\SmsGateway\MessageResponseTestFixturesTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for MessageResponseHandler, XmlResponseHandler, etc.
 *
 * @group SMS Gateway
 */
class MessageResponseHandlerTest extends UnitTestCase {

  use MessageResponseTestFixturesTrait;

  /**
   * @dataProvider providerMessageResponseHandler
   */
  public function testHandleMethod($raw, $expected_message_count, $expected_result) {
    $handler = new MessageResponseHandler();
    $result = $handler->handle($raw);
    $this->assertEquals($expected_message_count, count($result->getReports()));
    $this->assertEquals($expected_result, $result);
  }

  public function providerMessageResponseHandler() {
    return [
      [
        $this->testMessageResponse1,
        3,
        (new SmsMessageResult())
          ->setErrorMessage(new TranslatableMarkup('Message submitted successfully'))
          ->setReports([
            '41793026727' => (new SmsDeliveryReport())
              ->setStatus(SmsMessageReportStatus::QUEUED)
              ->setStatusMessage('Message accepted')
              ->setRecipient('41793026727')
              ->setMessageId('12db39c3-7822-4e72-a3ec-c87442c0ffc5'),

            '41793026731' => (new SmsDeliveryReport())
              ->setStatus(SmsMessageReportStatus::QUEUED)
              ->setStatusMessage('Message accepted')
              ->setRecipient('41793026731')
              ->setMessageId('bcfb828b-7df9-4e7b-8715-f34f5c61271a'),

            '41793026785' => (new SmsDeliveryReport())
              ->setStatus(SmsMessageReportStatus::QUEUED)
              ->setStatusMessage('Message accepted')
              ->setRecipient('41793026785')
              ->setMessageId('5f35f87a2f19-a141-43a4-91cd81b85f8c689'),
          ]),
      ],
    ];
  }
}
