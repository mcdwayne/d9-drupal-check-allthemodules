<?php

namespace Drupal\sms_infobip\Tests\Plugin\SmsGateway;

use Drupal\simpletest\WebTestBase;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms_gateway_base\Tests\Plugin\SmsGateway\GatewayTestTrait;
use Drupal\user\Entity\User;

/**
 * Tests Infobip delivery reports functionality and format.
 *
 * @group SMS Gateways
 */
class DeliveryReportsTest extends WebTestBase {

  use GatewayTestTrait;
  use MessageResponseTestFixturesTrait;

  public static $modules = [
    'sms', 'telephone', 'dynamic_entity_reference', 'sms_gateway_base',
    'sms_infobip', 'user',
  ];

  public function testDeliveryReports() {
    $gateway = $this->createSmsGateway('infobip', [
      'username' => 'test_user',
      'password' => 'password',
      'server' => 'api.infobip.com',
      'port' => 80,
    ]);
    $user = User::create(['name' => $this->randomMachineName()]);
    $sms_message = SmsMessage::create()
      ->setSenderEntity($user)
      // 'recipient' must match the number in $this->testMessageResponse2.
      ->addRecipient('41793026727')
      ->setMessage($this->randomString())
      ->setGateway($gateway);
    $sms_messages = $this->sendMockSms($sms_message, $this->testMessageResponse2);
    $sms_message = reset($sms_messages);

    // Save SMS message with the delivery reports.
    $sms_message->save();

    $reports = $sms_message->getReports();
    $this->assertEqual(count($reports), 1, 'Count of reports match.');
    $first_report = reset($reports);

    $this->assertEqual(SmsMessageReportStatus::QUEUED, $first_report->getStatus());
    $this->assertEqual("Message accepted", $first_report->getStatusMessage());
    $this->assertEqual(NULL, $first_report->getStatusTime());
    $this->assertEqual('2250be2d4219-3af1-78856-aabe-1362af1edfd2', $first_report->getMessageId());
    $this->assertEqual('41793026727', $first_report->getRecipient());

    // Simulate pushing delivery reports.
    $delivery_report = $this->testDeliveryReport2;
    $this->simulateDeliveryReportPush($gateway, ['Accept' => 'application/json'], $delivery_report);
    $this->resetAll();
    $sms_message = SmsMessage::load($sms_message->id());

    // Get the stored report and verify that it was properly parsed.
    $reports = $sms_message->getReports();
    $this->assertEqual(count($reports), 1, 'Count of second reports match.');

    $second_report = reset($reports);
    $this->assertEqual(SmsMessageReportStatus::DELIVERED, $second_report->getStatus());
    $this->assertEqual("Message delivered to handset", $second_report->getStatusMessage());
//    $this->assertEqual($delivered_time, $second_report->getTimeQueued());
    $delivered_time = strtotime("2017-08-22T09:55:43.123+0100");
    $this->assertEqual($delivered_time, $second_report->getTimeDelivered());
    $this->assertEqual($delivered_time, $second_report->getStatusTime());
    $this->assertEqual('2250be2d4219-3af1-78856-aabe-1362af1edfd2', $second_report->getMessageId());
    $this->assertEqual('41793026727', $second_report->getRecipient());
  }

}
