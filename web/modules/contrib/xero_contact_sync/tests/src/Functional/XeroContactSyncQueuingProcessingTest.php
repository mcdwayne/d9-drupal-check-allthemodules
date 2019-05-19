<?php

namespace Drupal\Tests\xero_contact_sync\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\User;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Radcliffe\Xero\XeroClient;
use GuzzleHttp\Psr7\Response;

/**
 * Tests the module queues execution when there is a content created.
 *
 * @group xero_contact_sync
 * @group legacy
 */
class XeroContactSyncQueuingProcessingTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['node', 'user', 'xero_contact_sync'];

  /**
   * @var \Psr\Log\LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $logger;

  /**
   * @var \Radcliffe\Xero\XeroClient|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $xeroClient;

  protected function setUp() {
    parent::setUp();
    $this->logger = $this->createMock(LoggerInterface::class);
    $this->xeroClient = $this->getMockBuilder(XeroClient::class)
      ->disableOriginalConstructor()
      ->getMock();

    $this->container->set('logger.channel.xero_contact_sync', $this->logger);
    $this->container->set('xero.client', $this->xeroClient);
  }

  public function testNoQueriesIfUserHasIdAlready() {
    $data = [
      'uid' => 23,
      'name' => 'Michael Jordan',
      'firstname' => 'Michael',
      'lastname' => 'Jordan',
      'mail' => 'mj23@example.com',
    ];

    // No log and no queries, we just return quickly.
    $this->logger->expects($this->never())
      ->method('log');

    $this->xeroClient->expects($this->never())
      ->method('__call');

    // We queue, and then we edit it.
    $user = User::create($data);
    $user->save();

    $user->set('xero_contact_id', '6819ba57-36bc-430e-bd04-3fd0dd3f3a2b');
    $user->save();

    $this->runQueueJob();

    $user = User::load(23);
    $this->assertEquals('6819ba57-36bc-430e-bd04-3fd0dd3f3a2b', $user->get('xero_contact_id')->value);
  }

  public function testUserDoesntExist() {
    $data = [
      'uid' => 23,
      'name' => 'Michael Jordan',
      'firstname' => 'Michael',
      'lastname' => 'Jordan',
      'mail' => 'mj23@example.com',
    ];
    $user = User::create($data);
    $user->save();
    $user->delete();

    // No queries, we just log and return quickly.
    $this->logger->expects($this->once())
      ->method('log')
      ->with(LogLevel::WARNING, 'There was no user with user id 23 for creating remotely.');

    $this->xeroClient->expects($this->never())
      ->method('__call');

    $this->runQueueJob();
  }

  public function testUserIsCreatedIfDidntExist() {
    $data = [
      'uid' => 23,
      'name' => 'Michael Jordan',
      'firstname' => 'Michael',
      'lastname' => 'Jordan',
      'mail' => 'mj23@example.com',
    ];
    $this->logger->expects($this->once())
      ->method('log')
      ->with(LogLevel::INFO, 'Created user Michael Jordan with remote id 6819ba57-36bc-430e-bd04-3fd0dd3f3a2b.');

    $response = $this->getMockResponseForEmpty();
    $this->xeroClient->expects($this->at(0))
      ->method('__call')
      ->with('get', ['Contacts', ['query' => ['where' => 'ContactNumber=="23"']]])
      ->willReturn($response);
    $this->xeroClient->expects($this->at(1))
      ->method('__call')
      ->with('get', ['Contacts', ['query' => ['where' => 'EmailAddress=="mj23@example.com"']]])
      ->willReturn($response);

    $response = $this->getMockResponseFor($data);
    $this->xeroClient->expects($this->at(2))
      ->method('__call')
      ->with('put')
      ->willReturn($response);

    $user = User::create($data);
    $user->save();

    $this->runQueueJob();

    $user = User::load(23);
    $this->assertEquals('6819ba57-36bc-430e-bd04-3fd0dd3f3a2b', $user->get('xero_contact_id')->value);
  }

  public function testUserIsReferencedIfFoundByContactNumber() {
    $data = [
      'uid' => 23,
      'name' => 'Michael Jordan',
      'firstname' => 'Michael',
      'lastname' => 'Jordan',
      'mail' => 'mj23@example.com',
    ];
    $this->logger->expects($this->once())
      ->method('log')
      ->with(LogLevel::INFO, 'User already found by contact number 23, assigned with remote id 6819ba57-36bc-430e-bd04-3fd0dd3f3a2b.');

    $response = $this->getMockResponseFor($data);
    $this->xeroClient->expects($this->at(0))
      ->method('__call')
      ->with('get', ['Contacts', ['query' => ['where' => 'ContactNumber=="23"']]])
      ->willReturn($response);

    $user = User::create($data);
    $user->save();

    $this->runQueueJob();

    $user = User::load(23);
    $this->assertEquals('6819ba57-36bc-430e-bd04-3fd0dd3f3a2b', $user->get('xero_contact_id')->value);
  }

  public function testUserIsReferencedIfFoundByEmail() {
    $data = [
      'uid' => 23,
      'name' => 'Michael Jordan',
      'firstname' => 'Michael',
      'lastname' => 'Jordan',
      'mail' => 'mj23@example.com',
    ];
    $this->logger->expects($this->once())
      ->method('log')
      ->with(LogLevel::INFO, 'User already found by email mj23@example.com, assigned with remote id 6819ba57-36bc-430e-bd04-3fd0dd3f3a2b.');

    $response = $this->getMockResponseForEmpty();
    $this->xeroClient->expects($this->at(0))
      ->method('__call')
      ->with('get', ['Contacts', ['query' => ['where' => 'ContactNumber=="23"']]])
      ->willReturn($response);

    $response = $this->getMockResponseFor($data);
    $this->xeroClient->expects($this->at(1))
      ->method('__call')
      ->with('get', ['Contacts', ['query' => ['where' => 'EmailAddress=="mj23@example.com"']]])
      ->willReturn($response);

    $user = User::create($data);
    $user->save();

    $this->runQueueJob();

    $user = User::load(23);
    $this->assertEquals('6819ba57-36bc-430e-bd04-3fd0dd3f3a2b', $user->get('xero_contact_id')->value);
  }

  protected function getMockResponseForEmpty() {
    $xml = '<Response xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <Id>1040dd4d-e6bd-4430-9844-6345fb214f38</Id>
  <Status>OK</Status>
  <ProviderName>Drupal Xero</ProviderName>
  <DateTimeUTC>2018-09-27T03:18:43.3794355Z</DateTimeUTC>
  </Response>';

    $response = new Response(
      200,
      [
        'Content-Type' => 'text/xml',
      ],
      $xml
    );

    return $response;
  }

  protected function getMockResponseFor($data) {
    $xml = '<Response xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
  <Id>1040dd4d-e6bd-4430-9844-6345fb214f38</Id>
  <Status>OK</Status>
  <ProviderName>Drupal Xero</ProviderName>
  <DateTimeUTC>2018-09-27T03:18:43.3794355Z</DateTimeUTC>
  <Contacts>
    <Contact>
      <ContactID>6819ba57-36bc-430e-bd04-3fd0dd3f3a2b</ContactID>
      <ContactNumber>8e9ffadf322833916682deecafe07caaad021b3958df59e0da</ContactNumber>
      <ContactStatus>ACTIVE</ContactStatus>
      <Name>' . $data['name'] . '</Name>
      <FirstName>' . $data['firstname'] . '</FirstName>
      <LastName>' . $data['lastname'] . '</LastName>
      <EmailAddress>' . $data['mail'] . '</EmailAddress>
      <Addresses>
        <Address>
          <AddressType>POBOX</AddressType>
        </Address>
        <Address>
          <AddressType>STREET</AddressType>
        </Address>
      </Addresses>
      <Phones>
        <Phone>
          <PhoneType>DDI</PhoneType>
        </Phone>
        <Phone>
          <PhoneType>DEFAULT</PhoneType>
        </Phone>
        <Phone>
          <PhoneType>FAX</PhoneType>
        </Phone>
        <Phone>
          <PhoneType>MOBILE</PhoneType>
        </Phone>
      </Phones>
      <UpdatedDateUTC>2018-09-20T14:51:53.56</UpdatedDateUTC>
      <IsSupplier>false</IsSupplier>
      <IsCustomer>false</IsCustomer>
      <HasAttachments>false</HasAttachments>
    </Contact>
  </Contacts>
  </Response>';

    $response = new Response(
      200,
      [
        'Content-Type' => 'text/xml',
      ],
      $xml
    );

    return $response;
  }

  protected function runQueueJob() {
    $queue_name = 'xero_contact_sync_create';
    /** @var \Drupal\Core\Queue\QueueWorkerInterface $queue_worker */
    $queue_worker = \Drupal::service('plugin.manager.queue_worker')
      ->createInstance($queue_name);

    $queue_worker->processItem(['user_id' => 23]);
  }

}
