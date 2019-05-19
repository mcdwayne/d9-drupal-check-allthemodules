<?php

namespace Drupal\Tests\xero\Unit\Plugin\DataType;

use Drupal\Core\TypedData\DataDefinition;
use Drupal\xero\TypedData\Definition\UserDefinition;
use Drupal\xero\Plugin\DataType\User;
use Drupal\Core\TypedData\Plugin\DataType\BooleanData;

/**
 * Assert setting and getting User properties.
 *
 * @coversDefaultClass \Drupal\xero\Plugin\DataType\User
 * @group Xero
 */
class UserTest extends TestBase {

  const XERO_TYPE = 'xero_user';
  const XERO_TYPE_CLASS = '\Drupal\xero\Plugin\DataType\User';
  const XERO_DEFINITION_CLASS = '\Drupal\xero\TypedData\Definition\UserDefinition';

  protected $user;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    // Create data type.
    $type_class = self::XERO_TYPE_CLASS;
    $this->user = new $type_class($this->dataDefinition, self::XERO_TYPE);
  }

  /**
   * Test getPropertyDefinitions.
   */
  public function testPropertyDefinitions() {
    $properties = $this->user->getProperties();

    $this->assertArrayHasKey('UserID', $properties);
    $this->assertArrayHasKey('EmailAddress', $properties);
    $this->assertArrayHasKey('FirstName', $properties);
    $this->assertArrayHasKey('LastName', $properties);
    $this->assertArrayHasKey('UpdatedDateUTC', $properties);
    $this->assertArrayHasKey('IsSubscriber', $properties);
    $this->assertArrayHasKey('OrganisationRole', $properties);
  }

  /**
   * Test isSubscriber method.
   */
  public function testIsSubscriber() {
    $boolean_def = DataDefinition::create('boolean');
    $boolean = new BooleanData($boolean_def);

    $this->typedDataManager->expects($this->any())
      ->method('getPropertyInstance')
      ->with($this->user, 'IsSubscriber')
      ->willReturn($boolean);

    $this->user->set('IsSubscriber', FALSE);
    $this->assertFalse($this->user->isSubscriber());

    $this->user->set('IsSubscriber', TRUE);
    $this->assertTrue($this->user->isSubscriber());
  }
}
