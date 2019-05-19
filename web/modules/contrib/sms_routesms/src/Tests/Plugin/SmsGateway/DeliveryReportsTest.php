<?php

namespace Drupal\sms_routesms\Tests\Plugin\SmsGateway;

use Drupal\simpletest\WebTestBase;
use Drupal\sms\Entity\SmsMessage;
use Drupal\sms\Message\SmsMessageReportStatus;
use Drupal\sms_gateway_base\Tests\Plugin\SmsGateway\GatewayTestTrait;
use Drupal\user\Entity\User;

/**
 * Tests Routesms delivery reports functionality and format.
 *
 * @group SMS Gateways
 */
class DeliveryReportsTest extends WebTestBase {

  use GatewayTestTrait;

  public static $modules = [
    'sms', 'telephone', 'dynamic_entity_reference', 'sms_gateway_base',
    'sms_routesms', 'user',
  ];

  public function testDeliveryReports() {
    $gateway = $this->createSmsGateway('routesms', [
      'username' => 'test_user',
      'password' => 'password',
      'server' => 'smsplus.routesms.com',
      'port' => 8080,
    ]);
    $user = User::create(['name' => $this->randomMachineName()]);
    /** @var \Drupal\sms\Entity\SmsMessageInterface $sms_message */
    $sms_message = SmsMessage::create()
      ->setSenderEntity($user)
      // 'recipient' must match the number in $this->testMessageResponse2.
      ->addRecipients(['123123123', '234234234', '678678678', '2345678990', '3456789012'])
      ->setMessage($this->randomString())
      ->setGateway($gateway);
    $sms_messages = $this->sendMockSms($sms_message, $this->testResults);
    $sms_message = reset($sms_messages);

    // Save SMS message with the delivery reports.
    $sms_message->save();

    $reports = $sms_message->getReports();
    $this->assertEqual(count($reports), 5, 'Count of reports match.');
    $first_report = $sms_message->getReport('123123123');

    $this->assertEqual(SmsMessageReportStatus::QUEUED, $first_report->getStatus());
    $this->assertEqual(NULL, $first_report->getStatusMessage());
//    $this->assertEqual($delivered_time, $second_report->getTimeQueued());
    $this->assertEqual(NULL, $first_report->getStatusTime());
    $this->assertEqual('bc5f7425-c98c-445b-a1f7-4fc5e2acef7e', $first_report->getMessageId());
    $this->assertEqual('123123123', $first_report->getRecipient());

    // Simulate pushing delivery reports.
    $this->simulateDeliveryReportPush($gateway, [], '', $this->testReportQuery);
    $this->resetAll();
    $sms_message = SmsMessage::load($sms_message->id());

    // Get the stored report and verify that it was properly parsed.
    $reports = $sms_message->getReports();
    $this->assertEqual(count($reports), 5, 'Count of second reports match.');

    $first_report = $sms_message->getReport('123123123');
    $this->assertEqual(SmsMessageReportStatus::DELIVERED, $first_report->getStatus());
    $this->assertEqual("DELIVRD", $first_report->getStatusMessage());
//    $this->assertEqual($delivered_time, $second_report->getTimeQueued());
    $delivered_time = strtotime($this->testReportQuery['dtDone'] . '.000+0000');
    $this->assertEqual($delivered_time, $first_report->getTimeDelivered());
    $this->assertEqual($delivered_time, $first_report->getStatusTime());
    $this->assertEqual('bc5f7425-c98c-445b-a1f7-4fc5e2acef7e', $first_report->getMessageId());
    $this->assertEqual('123123123', $first_report->getRecipient());
  }

  /**
   * Test message result.
   *
   * @var string
   */
  protected $testResults = '1701|123123123|bc5f7425-c98c-445b-a1f7-4fc5e2acef7e,1701|234234234|5122f879-2ba7-4469-8ae2-4091267ef389,1701|678678678|20cef313-1660-4b92-baa5-1b7ba45256a5,1025|2345678990';

  /**
   * Test delivery report.
   *
   * @var string
   */
  protected $testReportQuery = [
    'sSender' => 'TestGateway',
    'sMobileNo' => '123123123',
    'sStatus' => 'DELIVRD',
    'sMessageId' => 'bc5f7425-c98c-445b-a1f7-4fc5e2acef7e',
    'dtDone' => '2017-08-22 15:35:34',
    'dtSubmit' => '2017-08-22 15:46:34',
  ];

}
