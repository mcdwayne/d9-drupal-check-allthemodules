<?php

namespace Drupal\Tests\xero\Unit;

use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\Plugin\DataType\BooleanData;
use Drupal\Core\TypedData\Plugin\DataType\DateTimeIso8601;
use Drupal\Core\TypedData\Plugin\DataType\Email;
use Drupal\Core\TypedData\Plugin\DataType\StringData;
use Drupal\xero\Plugin\DataType\User;
use Drupal\xero\Plugin\DataType\XeroItemList;
use Drupal\xero\TypedData\Definition\UserDefinition;
use GuzzleHttp\Psr7\Response;

/**
 * Assert that the getCache and setCache methods work appropriately.
 *
 * @group Xero
 * @covers \Drupal\xero\XeroQuery
 */
class XeroQueryCacheTest extends XeroQueryTestBase {

  /**
   * @var \Drupal\xero\Plugin\DataType\User
   */
  protected $userData;

  /**
   * @var \Drupal\Core\TypedData\Plugin\DataType\ItemList
   */
  protected $listData;

  /**
   * @var \Drupal\Core\TypedData\ListDataDefinitionInterface
   */
  protected $listDefinition;

  /**
   * @var \Drupal\xero\TypedData\Definition\UserDefinition
   */
  protected $definition;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Create Xero user data.
    $this->definition = UserDefinition::create('xero_user');
    $this->definition->setClass('\Drupal\xero\Plugin\DataType\User');
    $this->userData = new User($this->definition);

    // Create list of Xero users.
    $this->listDefinition = ListDataDefinition::create('xero_user');
    $this->listDefinition->setClass('\Drupal\Core\TypedData\Plugin\DataType\ItemList');
    $this->listDefinition->setItemDefinition($this->definition);

    // Mock item list class because it depends on \Drupal via TypedDataTrait.
    $this->listData = XeroItemList::createInstance($this->listDefinition);

    $data = [
      [
        'UserID' => '7cf47fe2-c3dd-4c6b-9895-7ba767ba529c',
        'FirstName' => 'John',
        'LastName' => 'Smith',
        'EmailAddress' => 'john.smith@mail.com',
        'UpdatedDateUTC' => '2010-03-03T22:23:26.94',
        'IsSubscriber' => 'true',
        'OrganisationRole' => 'ADMIN',
      ],
    ];
    $dataMap = [
      [$this->listData, 0, NULL, $this->userData],
      [$this->listData, 0, $this->userData, $this->userData],
      [$this->userData, 'UserID', $data[0]['UserID']],
      [$this->userData, 'FirstName', $data[0]['FirstName']],
      [$this->userData, 'LastName', $data[0]['LastName']],
      [$this->userData, 'EmailAddress', $data[0]['EmailAddress']],
      [$this->userData, 'UpdatedDateUTC', $data[0]['UpdatedDateUTC']],
      [$this->userData, 'IsSubscriber', $data[0]['IsSubscriber']],
      [$this->userData, 'OrganisationRole', $data[0]['OrganisationRole']],
    ];
    $definitionMap = [
      [
        'id' => 'xero_user',
        'class' => '\Drupal\xero\Plugin\DataType\User',
        'definition class' => '\Drupal\xero\TypedData\Definition\UserDefinition'
      ]
    ];

    $this->userData->setValue($data[0]);

    // Create data types for each data definition
    /** @var \Drupal\Core\TypedData\DataDefinition $definition */
    foreach ($this->definition->getPropertyDefinitions() as $key => $definition) {
      foreach ($dataMap as $index => $item) {
        if ($item[1] === $key) {
          if ($definition->getDataType() === 'string') {
            $property = new StringData($definition, $key, $this->userData);
            $property->setValue($dataMap[$index][2]);
            $dataMap[$index][] = $property;
            $definition->setClass('\Drupal\Core\TypedData\Plugin\DataType\StringData');
          }
          elseif ($definition->getDataType() === 'email') {
            $property = new Email($definition, $key, $this->userData);
            $property->setValue($dataMap[$index][2]);
            $dataMap[$index][] = $property;
            $definition->setClass('\Drupal\Core\TypedData\Plugin\DataType\Email');
          }
          elseif ($definition->getDataType() === 'datetime_iso8601') {
            $property = new DateTimeIso8601($definition, $key, $this->userData);
            $property->setValue($dataMap[$index][2]);
            $dataMap[$index][] = $property;
            $definition->setClass('\Drupal\Core\TypedData\Plugin\DataType\DateTimeIso8061');
          }
          else {
            $property = new BooleanData($definition, $key, $this->userData);
            $property->setValue($dataMap[$index][2]);
            $dataMap[$index][] = $property;
            $definition->setClass('\Drupal\Core\TypedData\Plugin\DataType\BooleanData');
          }
          break;
        }
      }
      $definitionMap[] = [
        'id' => $definition->getDataType(),
        'class' => $definition->getClass(),
        'definition class' => get_class($definition)
      ];
    }

    $this->typedDataManager->expects($this->any())
      ->method('getPropertyInstance')
      ->will($this->returnValueMap($dataMap));
    $this->typedDataManager->expects($this->any())
      ->method('getDefinition')
      ->will(call_user_func_array([$this, 'onConsecutiveCalls'], $definitionMap));
    $this->typedDataManager->expects($this->any())
      ->method('createListDataDefinition')
      ->with('xero_user')
      ->willReturn($this->listDefinition);
    $this->typedDataManager->expects($this->any())
      ->method('createDataDefinition')
      ->with('xero_user')
      ->willReturn($this->definition);
    $this->typedDataManager->expects($this->any())
      ->method('create')
      ->will($this->returnValueMap(
        [
          [$this->listDefinition, [], NULL, NULL, $this->listData],
          [$this->definition, [], NULL, NULL, $this->userData],
        ]
      ));
  }

  /**
   * Assert that getCache works with a cache object set.
   */
  public function testGetCache() {
    $cached = new \StdClass();
    $cached->data = $this->listData;

    $this->cache->expects($this->any())
      ->method('get')
      ->with('xero_user')
      ->willReturn($cached);

    $data = $this->query->getCache('xero_user');
    $this->assertEquals($cached->data, $data);
  }

  /**
   * Assert that getCache works and cache is set.
   */
  public function testGetSetCache() {
    $this->client->expects($this->any())
      ->method('__call')
      ->with('get')
      ->willReturn($this->getMockResponse());

    /** @var \Drupal\xero\Plugin\DataType\XeroItemList $data */
    $data = $this->query->getCache('xero_user');
    $this->assertInstanceOf('\Drupal\xero\Plugin\DataType\XeroItemList', $data);
  }

  /**
   * Return expected XML string.
   *
   * @return \GuzzleHttp\Psr7\Response
   *   A mock response object.
   */
  protected function getMockResponse() {
    $xml = '<Response xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">
    <Id>' . $this->createGuid(FALSE) . '</Id>
    <Status>OK</Status>
    <ProviderName>Drupal Xero</ProviderName>
    <DateTimeUTC>2016-03-06T15:29:39.889128Z</DateTimeUTC>
    <Users>
      <User>
        <UserID>7cf47fe2-c3dd-4c6b-9895-7ba767ba529c</UserID>
        <FirstName>John</FirstName>
        <LastName>Smith</LastName>
        <EmailAddress>john.smith@mail.com</EmailAddress>
        <UpdatedDateUTC>2010-03-03T22:23:26.94</UpdatedDateUTC>
        <IsSubscriber>true</IsSubscriber>
        <OrganisationRole>ADMIN</OrganisationRole>
      </User>
    </Users>
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

}