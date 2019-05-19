<?php

namespace Drupal\Tests\sms_routesms\Unit\Plugin\SmsGateway\RouteSms;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\sms\Message\SmsDeliveryReport;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms\Message\SmsMessageResult;
use Drupal\sms\Message\SmsMessageResultInterface;
use Drupal\sms\Message\SmsMessageResultStatus;
use Drupal\sms_routesms\Plugin\SmsGateway\RouteSms\MessageResponseHandler;
use Drupal\Tests\sms_gateway_base\Unit\Plugin\SmsGateway\TestStringTranslationInterfaceTrait;
use Drupal\Tests\UnitTestCase;

/**
 * Tests for MessageResponseHandler.
 *
 * @group SMS Gateway
 */
class MessageResponseHandlerTest extends UnitTestCase {

  use TestStringTranslationInterfaceTrait;

  /**
   * @dataProvider providerMessageResponseHandler
   */
  public function testHandleMethod(array $recipients, $raw, $expected_report_count, SmsMessageResultInterface $expected_result) {
    $handler = new MessageResponseHandler($recipients);
    /** @var \Drupal\sms\Message\SmsMessageResultInterface $result */
    $result = $handler->handle($raw);
    $this->assertEquals($expected_report_count, count($result->getReports()));
    $this->assertEquals($expected_result, $result);
  }

  public function providerMessageResponseHandler() {
    return [
      [
        ['123123123', '234234234', '678678678'],
        '1701|123123123|bc5f7425-c98c-445b-a1f7-4fc5e2acef7e,1701|234234234|5122f879-2ba7-4469-8ae2-4091267ef389,1701|678678678|20cef313-1660-4b92-baa5-1b7ba45256a5',
        3,
        (new SmsMessageResult())
          ->setErrorMessage(new TranslatableMarkup('Message submitted successfully'))
          ->setReports([
            '123123123' => (new SmsDeliveryReport())
              ->setRecipient('123123123')
              ->setMessageId('bc5f7425-c98c-445b-a1f7-4fc5e2acef7e')
              ->setStatus(SmsMessageReportStatus::QUEUED),

            '234234234' => (new SmsDeliveryReport())
              ->setRecipient('234234234')
              ->setMessageId('5122f879-2ba7-4469-8ae2-4091267ef389')
              ->setStatus(SmsMessageReportStatus::QUEUED),

            '678678678' => (new SmsDeliveryReport())
              ->setRecipient('678678678')
              ->setMessageId('20cef313-1660-4b92-baa5-1b7ba45256a5')
              ->setStatus(SmsMessageReportStatus::QUEUED),
          ]),
      ],
      [
        ['23405678901', '23405', '3453453456'],
        '1701|23405678901|5122f879-2ba7-4469-8ae2-4091267ef389,1706|23405,1025|3453453456',
        3,
        (new SmsMessageResult())
          ->setErrorMessage(new TranslatableMarkup('Message submitted successfully'))
          ->setReports([
            '23405678901' => (new SmsDeliveryReport())
              ->setRecipient('23405678901')
              ->setMessageId('5122f879-2ba7-4469-8ae2-4091267ef389')
              ->setStatus(SmsMessageReportStatus::QUEUED),

            '23405' => (new SmsDeliveryReport())
              ->setRecipient('23405')
              ->setStatus(SmsMessageReportStatus::INVALID_RECIPIENT)
              ->setStatusMessage(new TranslatableMarkup('Invalid Destination')),

            '3453453456' => (new SmsDeliveryReport())
              ->setRecipient('3453453456')
              ->setStatus(SmsMessageResultStatus::NO_CREDIT)
              ->setStatusMessage(new TranslatableMarkup('Insufficient Credit')),
          ]),
      ],
      [
        ['23405678901'],
        '1702',
        0,
        (new SmsMessageResult())
          ->setError(SmsMessageResultStatus::PARAMETERS)
          ->setErrorMessage(new TranslatableMarkup('Invalid URL Error, This means that one of the parameters was not provided or left blank')),
      ],
      [
        ['23405678901'],
        '1703|987987987,1704|234509,1705|234059874545',
        0,
        (new SmsMessageResult())
          ->setError(SmsMessageResultStatus::PARAMETERS)
          ->setErrorMessage(new TranslatableMarkup('Invalid value in username or password field')),
      ],
      [
        ['987987987', '876876876', '765765765', '789789789', '123123123'],
        '1701|987987987|bc5f7425-c98c-445b-a1f7-4fc5e2acef7e,1701|876876876|5122f879-2ba7-4469-8ae2-4091267ef389,1025|765765765',
        5,
        (new SmsMessageResult())
          ->setErrorMessage(new TranslatableMarkup('Message submitted successfully'))
          ->setReports([
            '987987987' => (new SmsDeliveryReport())
              ->setRecipient('987987987')
              ->setMessageId('bc5f7425-c98c-445b-a1f7-4fc5e2acef7e')
              ->setStatus(SmsMessageReportStatus::QUEUED),

            '876876876' => (new SmsDeliveryReport())
              ->setRecipient('876876876')
              ->setMessageId('5122f879-2ba7-4469-8ae2-4091267ef389')
              ->setStatus(SmsMessageReportStatus::QUEUED),

            '765765765' => (new SmsDeliveryReport())
              ->setRecipient('765765765')
              ->setStatus(SmsMessageResultStatus::NO_CREDIT)
              ->setStatusMessage(new TranslatableMarkup('Insufficient Credit')),

            '789789789' => (new SmsDeliveryReport())
              ->setRecipient('789789789')
              ->setStatus(SmsMessageResultStatus::NO_CREDIT)
              ->setStatusMessage(new TranslatableMarkup('Insufficient Credit')),

            '123123123' => (new SmsDeliveryReport())
              ->setRecipient('123123123')
              ->setStatus(SmsMessageResultStatus::NO_CREDIT)
              ->setStatusMessage(new TranslatableMarkup('Insufficient Credit')),
          ]),
      ]
    ];
  }

}
