<?php

namespace Drupal\Tests\aws_cloud\Unit\Service;

use Drupal\Tests\UnitTestCase;

/**
 * Tests AWS Cloud Service.
 *
 * @group AWS Cloud
 */
class AwsEc2ServiceTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->service = new AwsEc2ServiceMock(
      $this->getMock('Drupal\Core\Entity\EntityTypeManagerInterface'),
      $this->getMock('Drupal\Core\Logger\LoggerChannelFactoryInterface'),
      $this->getConfigFactoryStub([
        'aws_cloud.settings' => ['aws_cloud_test_mode' => TRUE],
      ]),
      $this->getMockBuilder('Drupal\Core\Messenger\Messenger')
        ->disableOriginalConstructor()
        ->getMock(),
      $this->getStringTranslationStub(),
      $this->getMock('Drupal\Core\Session\AccountInterface'),
      $this->getMock('Drupal\cloud\Plugin\CloudConfigPluginManagerInterface'),
      $this->getMock('Drupal\Core\Field\FieldTypePluginManagerInterface'),
      $this->getMock('Drupal\Core\Entity\EntityFieldManagerInterface'),
      $this->getMock('Drupal\Core\Lock\LockBackendInterface')
    );
  }

  /**
   * Testing get availability zones.
   */
  public function testGetAvailabilityZones() {
    $random = $this->getRandomGenerator();

    $zones = [];
    $zones[] = $random->name(8, TRUE);
    $zones[] = $random->name(8, TRUE);
    $zones[] = $random->name(8, TRUE);

    $responseZones = [];
    $responseZones['AvailabilityZones'] = array_map(function ($zone) {
      return ['ZoneName' => $zone];
    }, $zones);
    $this->service->setAvailabilityZonesForTest($responseZones);

    $expectedResult = array_combine($zones, $zones);
    $actualResult = $this->service->getAvailabilityZones();
    $this->assertSame($expectedResult, $actualResult);
  }

}
