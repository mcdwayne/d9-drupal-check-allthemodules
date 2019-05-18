<?php

namespace Drupal\Tests\aws_cloud\Unit\Service;

use Drupal\aws_cloud\Service\AwsEc2Service;

/**
 * Mock class for AwsEc2Service.
 */
class AwsEc2ServiceMock extends AwsEc2Service {
  private $zones;

  /**
   * Set availability zones for test.
   *
   * @param array $zones
   *   Zones array.
   */
  public function setAvailabilityZonesForTest(array $zones) {
    $this->zones = $zones;
  }

  /**
   * {@inheritdoc}
   */
  public function describeAvailabilityZones(array $params = []) {
    return $this->zones;
  }

}
