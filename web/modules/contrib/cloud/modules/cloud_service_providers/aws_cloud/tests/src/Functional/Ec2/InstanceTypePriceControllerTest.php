<?php

namespace Drupal\Tests\aws_cloud\Functional\Ec2;

/**
 * Tests AWS Cloud Instace Type Price.
 *
 * @group AWS Cloud
 */
class InstanceTypePriceControllerTest extends AwsCloudTestCase {

  const AWS_CLOUD_PRICE_REPEAT_COUNT = 3;

  /**
   * {@inheritdoc}
   */
  protected function getPermissions() {
    return [
      'view aws cloud instance type prices',
    ];
  }

  /**
   * Tests displaying prices.
   */
  public function testShowPrice() {
    $this->repeatTestShowPrice(self::AWS_CLOUD_PRICE_REPEAT_COUNT);
  }

  /**
   * Repeats testing displaying prices.
   *
   * @param int $max_test_repeat_count
   *   Max test repeating count.
   */
  private function repeatTestShowPrice($max_test_repeat_count = 1) {
    $cloud_context = $this->cloudContext;
    for ($i = 0; $i < $max_test_repeat_count; $i++) {
      $this->drupalGet("/clouds/aws_cloud/$cloud_context/instance_type_price");

      $this->assertResponse(200);
      $this->assertText(t('AWS Cloud Instance Type Prices'));
      foreach (aws_cloud_get_instance_types($cloud_context) as $instance_type_data) {
        $parts = explode(':', $instance_type_data);
        $this->assertText($parts[0]);
      }
    }
  }

}
