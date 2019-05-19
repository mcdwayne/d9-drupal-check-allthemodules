<?php

namespace Drupal\Tests\xero\Unit;

use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\xero\Plugin\DataType\XeroItemList;
use Drupal\xero\TypedData\Definition\AccountDefinition;
use Drupal\xero\Plugin\DataType\User;
use Drupal\xero\TypedData\Definition\UserDefinition;

/**
 * @group Xero
 */
class XeroQuerySettersTest extends XeroQueryTestBase {

  /**
   * Assert that setType works.
   *
   * @todo test the exception handling when PHPUnit's throwException is not a
   * broken mess that just returns class does not exist for a namespaced
   * exception.
   */
  public function testSetType() {
    $accountDefinition = AccountDefinition::create('xero_account');

    $this->typedDataManager->expects($this->any())
      ->method('getDefinition')
      ->with('xero_account')
      ->willReturn($accountDefinition);

    $this->query->setType('xero_account');
    $this->assertEquals('xero_account', $this->query->getType());
    $this->assertSame($accountDefinition, $this->query->getDefinition());
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testSetBadMethod() {
    $this->query->setMethod('garbage');
  }

  /**
   * Assert that setMethod works.
   */
  public function testSetMethod() {
    $this->query->setMethod('get');
    $this->assertEquals('get', $this->query->getMethod());

    $this->query->setMethod('post');
    $this->assertEquals('post', $this->query->getMethod());
    $this->assertEquals('xml', $this->query->getFormat('xml'));
    $this->assertEquals('text/xml;charset=UTF-8', $this->query->getOptions()['headers']['Content-Type']);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testSetBadFormat() {
    $this->query->setFormat('garbage');
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testSetBadId() {
    $this->query->setId('garbage');
  }

  /**
   * Assert that setID works.
   */
  public function testSetId() {
    $guid = $this->createGuid(FALSE);
    $this->query->setId($guid);
    $this->assertEquals($guid, $this->query->getId());
  }

  /**
   * Assert that setModifiedAfter works.
   */
  public function testSetModifiedAfter() {
    $now = time();
    $this->query->SetModifiedAfter($now);
    $this->assertEquals($now, $this->query->getOptions()['headers']['If-Modified-Since']);
  }

  /**
   * @expectedException InvalidArgumentException
   */
  public function testSetBadData() {
    $accountDefinition = AccountDefinition::create('xero_account');
    $userDefinition = UserDefinition::create('xero_user');

    $this->typedDataManager->expects($this->any())
      ->method('getDefinition')
      ->withConsecutive(
        array('xero_account'),
        array('xero_user')
      )
      ->will($this->onConsecutiveCalls($accountDefinition, $userDefinition));

    $listDefinition = ListDataDefinition::createFromDataType('xero_user');
    $users = XeroItemList::createInstance($listDefinition);

    $this->query->setType('xero_account');
    $this->query->setData($users);
  }

  /**
   * Assert that setData is working properly when type is not set.
   */
  public function testSetData() {
    $userDefinition = UserDefinition::create('xero_user');

    $this->typedDataManager->expects($this->any())
      ->method('getDefinition')
      ->with('xero_user')
      ->willReturn($userDefinition);

    $listDefinition = ListDataDefinition::createFromDataType('xero_user');
    $users = XeroItemList::createInstance($listDefinition);

    $this->query->setData($users);
    $this->assertSame($users, $this->query->getData());
  }
}
