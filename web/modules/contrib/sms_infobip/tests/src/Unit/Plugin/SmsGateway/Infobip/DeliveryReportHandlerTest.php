<?php

namespace Drupal\Tests\sms_infobip\Unit\Plugin\SmsGateway\Infobip;

use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms_infobip\Plugin\SmsGateway\Infobip\DeliveryReportHandler;
use Drupal\sms_infobip\Tests\Plugin\SmsGateway\MessageResponseTestFixturesTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for MessageResponseHandler, XmlResponseHandler, etc.
 *
 * @group SMS Gateway
 */
class DeliveryReportHandlerTest extends UnitTestCase {

  use MessageResponseTestFixturesTrait;

  /**
   * @dataProvider providerMessageResponseHandler
   */
  public function testHandleMethod($raw, $expected_message_count, array $expected_result) {
    $handler = new DeliveryReportHandler();
    $result = $handler->handle($raw);
    $this->assertEquals($expected_message_count, count($result->getReports()));
    $this->assertEquals($expected_result, $result->getReports());
  }

  public function providerMessageResponseHandler() {
    return [
      [
        $this->testDeliveryReport1,
        3,
        [
          (new SmsDeliveryReport())
            ->setRecipient('41793026727')
            ->setMessageId('12db39c3-7822-4e72-a3ec-c87442c0ffc5')
            ->setStatus(SmsMessageReportStatus::DELIVERED)
            ->setStatusMessage('Message delivered to handset')
            ->setTimeQueued(strtotime("2015-02-12T09:50:22.221+0100"))
            ->setTimeDelivered(strtotime("2015-02-12T09:50:22.232+0100"))
            ->setStatusTime(strtotime('2015-02-12T09:50:22.232+0100')),

          (new SmsDeliveryReport())
            ->setRecipient('41793026731')
            ->setMessageId('bcfb828b-7df9-4e7b-8715-f34f5c61271a')
            ->setStatus(SmsMessageReportStatus::DELIVERED)
            ->setStatusMessage('Message delivered to handset')
            ->setTimeQueued(strtotime('2015-02-12T09:51:43.123+0100'))
            ->setTimeDelivered(strtotime('2015-02-12T09:51:43.127+0100'))
            ->setStatusTime(strtotime('2015-02-12T09:51:43.127+0100')),

          // @TODO Change this third one to a failed report.
          (new SmsDeliveryReport())
            ->setRecipient('41793026785')
            ->setMessageId('5f35f87a2f19-a141-43a4-91cd81b85f8c689')
            ->setStatus(SmsMessageReportStatus::DELIVERED)
            ->setStatusMessage('Message delivered to handset')
            ->setTimeQueued(strtotime("2017-02-12T09:55:43.123+0100"))
            ->setTimeDelivered(strtotime("2017-02-12T09:56:43.127+0100"))
            ->setStatusTime(strtotime('2017-02-12T09:56:43.127+0100')),
        ],
      ],
    ];
  }
}
