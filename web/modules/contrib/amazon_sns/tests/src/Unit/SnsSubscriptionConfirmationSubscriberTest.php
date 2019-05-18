<?php

namespace Drupal\Tests\amazon_sns\Unit;

use Aws\Sns\Message;
use Drupal\amazon_sns\Event\SnsMessageEvent;
use Drupal\amazon_sns\Event\SnsSubscriptionConfirmationSubscriber;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use Psr\Log\LoggerInterface;

/**
 * Test confirming new SNS subscriptions.
 *
 * @group amazon_sns
 */
class SnsSubscriptionConfirmationSubscriberTest extends UnitTestCase {

  /**
   * Test subscription confirmation.
   */
  public function testConfirm() {
    // This message does not have a valid signature.
    $data = [
      "Type" => "SubscriptionConfirmation",
      "MessageId" => "f93edf3e-bee9-57f3-8752-8e97b283e829",
      "TopicArn" => "arn:aws:sns:us-east-1:222524823419:drupal-sns-test",
      "SubscribeURL" => "http://example.com/confirm",
      "Message" => "empty",
      "Timestamp" => "2017-05-31T18:23:38.935Z",
      "SignatureVersion" => "1",
      "Signature" => "D7g1ZmCjj41EsrKlDiRunlS8AsUbI009XScwGKOAryWmCP2wDCb1j7ZR3LDJpkM9ayZRwx5NQMZ18NKnji0iE6Lw5DCGzC93fVKXy7IdZWeApg7gfuXeOu9FpxzjuaY03kbkSzKDWMdJjO0DgBXsJXoUi2gi0AD4ED+yutn7hkDYjW9tq5SzJP9XRp4fXhEDPi1DEP8luNnfyDUcSxvKCFiOaHlkTnps1bvorT5Kr6dmVS/RKf70LNTSKi8bsF/oFGHHAIQJ687OtW2Id0cxVtaPSNnPvf/z9IecZFpflvQEHsqdaC20eAmnP376sAoeAqFsEo81aUxmPXCMDYOPqg==",
      "SigningCertURL" => "https://sns.us-east-1.amazonaws.com/SimpleNotificationService-b95095beb82e8f6a046b3aafc7f4149a.pem",
      "UnsubscribeURL" => "https://sns.us-east-1.amazonaws.com/?Action=Unsubscribe&SubscriptionArn=arn:aws:sns:us-east-1:222524823419:drupal-sns-test:84a1d410-a187-44b9-b611-e82307fceb87",
      "Token" => "a token",
    ];

    $message = new Message($data);
    /** @var \PHPUnit_Framework_MockObject_MockObject|\GuzzleHttp\Client $client */
    $client = $this->getMockBuilder(Client::class)
      ->disableOriginalConstructor()
      ->getMock();
    $client->expects($this->once())->method('request')
      ->with('GET', $message['SubscribeURL']);

    /** @var \PHPUnit_Framework_MockObject_MockObject|\Psr\Log\LoggerInterface $logger */
    $logger = $this->getMockBuilder(LoggerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $logger->expects($this->once())->method('info');

    $subscriber = new SnsSubscriptionConfirmationSubscriber($client, $logger);
    $subscriber->confirm(new SnsMessageEvent($message));
  }

}
