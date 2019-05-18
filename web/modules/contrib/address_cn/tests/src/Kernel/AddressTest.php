<?php

namespace Drupal\Tests\address_cn\Kernel;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Tests\graphql\Kernel\GraphQLFileTestBase;
use Drupal\Tests\node\Traits\ContentTypeCreationTrait;
use Drupal\Tests\node\Traits\NodeCreationTrait;
use Drupal\user\Entity\Role;

class AddressTest extends GraphQLFileTestBase {

  use ContentTypeCreationTrait;
  use NodeCreationTrait;

  /**
   * Modules to install.
   *
   * @var array
   */
  public static $modules = [
    'field',
    'filter',
    'text',
    'node',
    'graphql_core',
    'address',
    'address_cn',
    'address_cn_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->installConfig(['filter', 'node']);
    $this->installEntitySchema('node');
    $this->installSchema('node', 'node_access');

    $this->createContentType([
      'type' => 'test',
    ]);

    Role::load('anonymous')
      ->grantPermission('access content')
      ->save();

    FieldStorageConfig::create([
      'field_name' => 'test_address',
      'entity_type' => 'node',
      'type' => 'address',
    ])->save();
    FieldConfig::create([
      'field_name' => 'test_address',
      'entity_type' => 'node',
      'bundle' => 'test',
      'label' => 'Test Address',
    ])->save();
  }

  /**
   * Tests address cn manager.
   */
  public function testAddressCnManager() {
    /** @var \Drupal\address_cn\AddressCnManagerInterface $address_cn_manager */
    $address_cn_manager = $this->container->get('address_cn.manager');

    $this->assertTrue($address_cn_manager->hasChildren('Beijing', ['CN']));
    $this->assertFalse($address_cn_manager->hasChildren('Changping Qu', [
      'CN',
      'Beijing Shi',
    ]));
    $this->assertTrue($address_cn_manager->hasChildren('Guangdong Sheng', ['CN']));
    $this->assertTrue($address_cn_manager->hasChildren('Shenzhen Shi', [
      'CN',
      'Guangdong Sheng',
    ]));
    $this->assertFalse($address_cn_manager->hasChildren('Bao\'an Qu', [
      'CN',
      'Guangdong Sheng',
      'Shenzhen Shi',
    ]));
    $this->assertFalse($address_cn_manager->hasChildren('Dongguan Shi', [
      'CN',
      'Guangdong Sheng',
    ]));
  }

  /**
   * Tests address subdivisions.
   */
  public function testAddressSubdivisions() {
    $result = $this->requestWithQueryFile('address_subdivisions.gql');
    $data = $result['data']['addressSubdivisions'];
    $this->assertEquals(34, count($data));
    $this->assertEquals([
      'code' => 'Beijing Shi',
      'name' => '北京市',
      'hasChildren' => TRUE,
    ], $data[0]);

    $result = $this->requestWithQueryFile('address_subdivisions.gql', [
      'parents' => ['CN', 'Beijing Shi'],
    ]);
    $data = $result['data']['addressSubdivisions'];
    $this->assertEquals(16, count($data));
    $this->assertEquals([
      'code' => 'Changping Qu',
      'name' => '昌平区',
      'hasChildren' => FALSE,
    ], $data[0]);

    $result = $this->requestWithQueryFile('address_subdivisions.gql', [
      'parents' => ['CN', 'Guangdong Sheng'],
    ]);
    $data = $result['data']['addressSubdivisions'];
    $this->assertEquals(21, count($data));
    $this->assertEquals([
      'code' => 'Shenzhen Shi',
      'name' => '深圳市',
      'hasChildren' => TRUE,
    ], $data[14]);
    $this->assertEquals([
      'code' => 'Dongguan Shi',
      'name' => '东莞市',
      'hasChildren' => FALSE,
    ], $data[1]);
  }

  /**
   * Tests address field formatter.
   */
  public function testAddressFieldFormatter() {
    // Beijing Shi has no district.
    $node = $this->createNode([
      'type' => 'test',
      'test_address' => [
        'langcode' => 'zh-hans',
        'country_code' => 'CN',
        'administrative_area' => 'Beijing Shi',
        'locality' => 'Changping Qu',
        'postal_code' => '100000',
        'address_line1' => 'Wang fu jing',
        'address_line2' => 'xxx',
        'organization' => 'xxx',
        'family_name' => 'Ma yun',
        'given_name' => '13812345678',
      ],
    ]);
    $node->save();
    $result = $this->requestWithQueryFile('address_field.gql', ['path' => '/node/' . $node->id()]);
    $address = $result['data']['route']['node']['testAddress'];
    $this->assertEquals([
      'countryCode' => 'CN',
      'contact' => 'Ma yun',
      'phone' => '13812345678',
      'province' => [
        'code' => 'Beijing Shi',
        'name' => '北京市',
        'hasChildren' => TRUE,
      ],
      'city' => [
        'code' => 'Changping Qu',
        'name' => '昌平区',
        'hasChildren' => FALSE,
      ],
      'district' => NULL,
      'postCode' => '100000',
      'streetAddress' => 'Wang fu jing',
    ], $address, 'Address resolved properly');

    // Guangdong address.
    $guang_dong_address = [
      'country_code' => 'CN',
      'administrative_area' => 'Guangdong Sheng',
      'locality' => 'Shenzhen Shi',
      'dependent_locality' => 'Bao\'an Qu',
      'postal_code' => '518000',
      'address_line1' => 'Xinan road, ZhongLi startup center',
      'address_line2' => 'xxx',
      'organization' => 'xxx',
      'family_name' => 'Ma yun',
      'given_name' => '13812345678',
    ];

    // With default value.
    $node = $this->createNode([
      'type' => 'test',
      'test_address' => $guang_dong_address,
    ]);
    $node->save();
    $result = $this->requestWithQueryFile('address_field.gql', ['path' => '/node/' . $node->id()]);
    $address = $result['data']['route']['node']['testAddress'];
    $this->assertEquals([
      'countryCode' => 'CN',
      'contact' => 'Ma yun',
      'phone' => '13812345678',
      'province' => [
        'code' => 'Guangdong Sheng',
        'name' => 'Guangdong Sheng',
        'hasChildren' => TRUE,
      ],
      'city' => [
        'code' => 'Shenzhen Shi',
        'name' => 'Shenzhen Shi',
        'hasChildren' => TRUE,
      ],
      'district' => [
        'code' => 'Bao\'an Qu',
        'name' => 'Bao\'an Qu',
        'hasChildren' => FALSE,
      ],
      'postCode' => '518000',
      'streetAddress' => 'Xinan road, ZhongLi startup center',
    ], $address, 'Address resolved properly');

    // Set langcode to 'zh-hans'.
    $guang_dong_address['langcode'] = 'zh-hans';
    $node = $this->createNode([
      'type' => 'test',
      'test_address' => $guang_dong_address,
    ]);
    $node->save();
    $result = $this->requestWithQueryFile('address_field.gql', ['path' => '/node/' . $node->id()]);
    $address = $result['data']['route']['node']['testAddress'];
    $this->assertEquals([
      'countryCode' => 'CN',
      'contact' => 'Ma yun',
      'phone' => '13812345678',
      'province' => [
        'code' => 'Guangdong Sheng',
        'name' => '广东省',
        'hasChildren' => TRUE,
      ],
      'city' => [
        'code' => 'Shenzhen Shi',
        'name' => '深圳市',
        'hasChildren' => TRUE,
      ],
      'district' => [
        'code' => 'Bao\'an Qu',
        'name' => '宝安区',
        'hasChildren' => FALSE,
      ],
      'postCode' => '518000',
      'streetAddress' => 'Xinan road, ZhongLi startup center',
    ], $address, 'Address resolved properly');

    // Set non standard city code, then itself and subdivisions under it will
    // keep unchanged.
    $guang_dong_address['locality'] = 'Shenzhen';
    $node = $this->createNode([
      'type' => 'test',
      'test_address' => $guang_dong_address,
    ]);
    $node->save();
    $result = $this->requestWithQueryFile('address_field.gql', ['path' => '/node/' . $node->id()]);
    $address = $result['data']['route']['node']['testAddress'];
    $this->assertEquals([
      'countryCode' => 'CN',
      'contact' => 'Ma yun',
      'phone' => '13812345678',
      'province' => [
        'code' => 'Guangdong Sheng',
        'name' => '广东省',
        'hasChildren' => TRUE,
      ],
      'city' => [
        'code' => 'Shenzhen',
        'name' => 'Shenzhen',
        'hasChildren' => FALSE,
      ],
      'district' => [
        'code' => 'Bao\'an Qu',
        'name' => 'Bao\'an Qu',
        'hasChildren' => FALSE,
      ],
      'postCode' => '518000',
      'streetAddress' => 'Xinan road, ZhongLi startup center',
    ], $address, 'Address resolved properly');
  }

}
