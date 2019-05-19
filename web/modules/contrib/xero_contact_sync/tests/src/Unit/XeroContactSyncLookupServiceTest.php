<?php

namespace Drupal\Tests\xero_contact_sync\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\xero\Plugin\DataType\Contact;
use Drupal\xero\Plugin\DataType\XeroItemList;
use Drupal\xero\XeroQuery;
use Drupal\xero\XeroQueryFactory;
use Drupal\xero_contact_sync\XeroContactSyncLookupService;

/**
 * @coversDefaultClass \Drupal\xero_contact_sync\XeroContactSyncLookupService
 * @group xero_contact_sync
 */
class XeroContactSyncLookupServiceTest extends UnitTestCase {

  /**
   * A Xero query.
   *
   * @var \Drupal\xero\XeroQueryFactory|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $xeroQueryFactory;

  /**
   * @var \Drupal\xero_contact_sync\XeroContactSyncLookupService
   */
  protected $lookupService;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->xeroQueryFactory = $this->getMockBuilder(XeroQueryFactory::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->lookupService = new XeroContactSyncLookupService($this->xeroQueryFactory);
  }

  /**
   * @covers \Drupal\xero_contact_sync\XeroContactSyncLookupService::lookupByContactNumber
   */
  public function testLookupByContactNumber() {
    $contact = $this->getMockBuilder(Contact::class)
      ->disableOriginalConstructor()
      ->getMock();
    $xeroQuery = $this->getMockBuilder(XeroQuery::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->xeroQueryFactory->expects($this->any())
      ->method('get')
      ->willReturn($xeroQuery);

    $contactList = $this->getMockBuilder(XeroItemList::class)
      ->disableOriginalConstructor()
      ->getMock();
    $contactList->expects($this->any())
      ->method('get')
      ->with(0)
      ->willReturn($contact);

    $contactList->expects($this->any())
      ->method('count')
      ->willReturn(1);

    $xeroQuery->expects($this->any())
      ->method('execute')
      ->willReturn($contactList);

    $contact = $this->lookupService->lookupByContactNumber('1234');
    $this->assertNotNull($contact);
  }

  /**
   * @covers \Drupal\xero_contact_sync\XeroContactSyncLookupService::lookupByContactNumber
   */
  public function testLookupByContactNumberNull() {
    $this->xeroQueryFactory->expects($this->never())
      ->method('get');

    $contact = $this->lookupService->lookupByContactNumber(NULL);
    $this->assertFalse($contact);
  }

  /**
   * @covers \Drupal\xero_contact_sync\XeroContactSyncLookupService::lookupByContactNumber
   */
  public function testLookupByContactNumberNotFound() {
    $contact = $this->getMockBuilder(Contact::class)
      ->disableOriginalConstructor()
      ->getMock();
    $xeroQuery = $this->getMockBuilder(XeroQuery::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->xeroQueryFactory->expects($this->any())
      ->method('get')
      ->willReturn($xeroQuery);

    $contactList = $this->getMockBuilder(XeroItemList::class)
      ->disableOriginalConstructor()
      ->getMock();
    $contactList->expects($this->any())
      ->method('get')
      ->with(0)
      ->willReturn($contact);

    $contactList->expects($this->any())
      ->method('count')
      ->willReturn(0);

    $xeroQuery->expects($this->any())
      ->method('execute')
      ->willReturn($contactList);

    $contact = $this->lookupService->lookupByContactNumber('not-found');
    $this->assertFalse($contact);
  }

  /**
   * @covers \Drupal\xero_contact_sync\XeroContactSyncLookupService::lookupByContactEmailAddress
   */
  public function testLookupByContactEmailAddress() {
    $contact = $this->getMockBuilder(Contact::class)
      ->disableOriginalConstructor()
      ->getMock();
    $xeroQuery = $this->getMockBuilder(XeroQuery::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->xeroQueryFactory->expects($this->any())
      ->method('get')
      ->willReturn($xeroQuery);

    $contactList = $this->getMockBuilder(XeroItemList::class)
      ->disableOriginalConstructor()
      ->getMock();
    $contactList->expects($this->any())
      ->method('get')
      ->with(0)
      ->willReturn($contact);

    $contactList->expects($this->any())
      ->method('count')
      ->willReturn(1);

    $xeroQuery->expects($this->any())
      ->method('execute')
      ->willReturn($contactList);

    $contact = $this->lookupService->lookupByContactEmailAddress('good@example.com');
    $this->assertNotNull($contact);
  }

  /**
   * @covers \Drupal\xero_contact_sync\XeroContactSyncLookupService::lookupByContactEmailAddress
   */
  public function testLookupByContactEmailAddressNull() {
    $this->xeroQueryFactory->expects($this->never())
      ->method('get');

    $contact = $this->lookupService->lookupByContactEmailAddress(NULL);
    $this->assertFalse($contact);
  }

  /**
   * @covers \Drupal\xero_contact_sync\XeroContactSyncLookupService::lookupByContactEmailAddress
   */
  public function testLookupByContactEmailAddressNotFound() {
    $contact = $this->getMockBuilder(Contact::class)
      ->disableOriginalConstructor()
      ->getMock();
    $xeroQuery = $this->getMockBuilder(XeroQuery::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->xeroQueryFactory->expects($this->any())
      ->method('get')
      ->willReturn($xeroQuery);

    $contactList = $this->getMockBuilder(XeroItemList::class)
      ->disableOriginalConstructor()
      ->getMock();
    $contactList->expects($this->any())
      ->method('get')
      ->with(0)
      ->willReturn($contact);

    $contactList->expects($this->any())
      ->method('count')
      ->willReturn(0);

    $xeroQuery->expects($this->any())
      ->method('execute')
      ->willReturn($contactList);

    $contact = $this->lookupService->lookupByContactEmailAddress('not-found');
    $this->assertFalse($contact);
  }

}
