<?php

namespace Drupal\Tests\aws_cloud\Traits;

use Drupal\Component\Utility\Random;
use Drupal\Tests\aws_cloud\Functional\Utils;
use Drupal\aws_cloud\Entity\Ec2\ElasticIp;
use Drupal\aws_cloud\Entity\Ec2\Instance;
use Drupal\aws_cloud\Entity\Ec2\NetworkInterface;
use Drupal\aws_cloud\Entity\Ec2\Snapshot;
use Drupal\aws_cloud\Entity\Ec2\Volume;

/**
 * The trait for aws cloud testing.
 */
trait AwsCloudTestTrait {

  /**
   * Internal random number.
   *
   * @var int
   */
  private static $internalRandom;

  /**
   * Generates a random string for AWS long ID.
   *
   * The string is containing 32 length-letters and numbers.
   *
   * @return string
   *   32 length randomly generated string.
   *
   * @see https://www.drupal.org/project/cloud/issues/3025228
   */
  protected function getRandomAwsId() {
    if (!isset(self::$internalRandom)) {
      self::$internalRandom = new Random();
    }

    return self::$internalRandom->name(32, TRUE);
  }

  /**
   * Create an AWS cloud instance.
   *
   * @param int $num
   *   The index.
   * @param string $public_ip
   *   The public ip.
   */
  protected function createTestInstance($num, $public_ip = NULL) {
    $name = $this->random->name(8, TRUE);
    if (!isset($public_ip)) {
      $public_ip = Utils::getRandomPublicIp();
    }
    $private_ip = Utils::getRandomPrivateIp();
    $regions = ['us-west-1', 'us-west-2'];
    $region = $regions[array_rand($regions)];

    $instance = Instance::create([
      'cloud_context' => $this->cloudContext,
      'name' => "Instance #$name - " . date('Y/m/d - ') . $this->random->name(8, TRUE),
      'image_id' => 'ami-' . $this->getRandomAwsId(),
      'key_pair_name' => "key_pair-$name-" . $this->random->name(8, TRUE),
      'is_monitoring' => 0,
      'availability_zone' => "us-west-$num",
      'security_groups' => "security_group-$name-" . $this->random->name(8, TRUE),
      'instance_type' => "t$num.small",
      'kernel_id' => 'aki-' . $this->getRandomAwsId(),
      'ramdisk_id' => 'ari-' . $this->getRandomAwsId(),
      'user_data' => "User Data #$num: " . $this->random->string(64, TRUE),
      'account_id' => rand(100000000000, 999999999999),
      'reservation_id' => 'r-' . $this->getRandomAwsId(),
      'group_name' => $this->random->name(8, TRUE),
      'host_id' => $this->random->name(8, TRUE),
      'affinity' => $this->random->name(8, TRUE),
      'launch_time' => date('c'),
      'security_group_id' => 'sg-' . $this->getRandomAwsId(),
      'security_group_name' => $this->random->name(10, TRUE),
      'public_dns_name' => Utils::getPublicDns($region, $public_ip),
      'public_ip_address' => $public_ip,
      'private_dns_name' => Utils::getPrivateDns($region, $private_ip),
      'private_ip_address' => $private_ip,
      'vpc_id' => 'vpc-' . $this->getRandomAwsId(),
      'subnet_id' => 'subnet-' . $this->getRandomAwsId(),
      'image_id' => 'ami-' . $this->getRandomAwsId(),
      'reason' => $this->random->string(16, TRUE),
      'instance_id' => 'i-' . $this->getRandomAwsId(),
      'uid' => $this->loggedInUser->id(),
    ]);
    $instance->save();
    return $instance;
  }

  /**
   * Create network interface test entity.
   *
   * @param int $num
   *   The index.
   * @param string $instance_id
   *   The instance id.
   *
   * @return \Drupal\aws_cloud\Entity\Ec2\NetworkInterface
   *   The network interface entity.
   */
  protected function createTestNetworkInterface($num, $instance_id) {
    $timestamp = time();
    $private_ip = Utils::getRandomPrivateIp();
    $secondary_private_ip = Utils::getRandomPrivateIp();

    $network_interface = NetworkInterface::create([
      'cloud_context' => $this->cloudContext,
      'name' => "NetworkInterface #$num - " . date('Y/m/d - ') . $this->random->name(8, TRUE),
      'network_interface_id' => 'eni-' . $this->getRandomAwsId(),
      'vpc_id' => "vpc-" . $this->random->name(8, TRUE),
      'mac_address' => NULL,
      'security_groups' => "security_group-" . $this->random->name(8, TRUE),
      'status' => 'in-use',
      'private_dns' => NULL,
      'primary_private_ip' => $private_ip,
      'secondary_private_ips' => [$secondary_private_ip],
      'attachment_id' => NULL,
      'attachment_owner' => NULL,
      'attachment_status' => NULL,
      'owner_id' => rand(100000000000, 999999999999),
      'association_id' => NULL,
      'secondary_association_id' => NULL,
      'subnet_id' => NULL,
      'description' => NULL,
      'public_ips' => NULL,
      'source_dest_check' => NULL,
      'instance_id' => $instance_id,
      'device_index' => NULL,
      'delete_on_termination' => NULL,
      'allocation_id' => NULL,
      'created' => $timestamp,
      'changed' => $timestamp,
      'refreshed' => $timestamp,
    ]);
    $network_interface->save();
    return $network_interface;
  }

  /**
   * Create elastic ip test entity.
   *
   * @param int $num
   *   The index.
   *
   * @return \Drupal\aws_cloud\Entity\Ec2\ElasticIp
   *   The elastic ip entity.
   */
  protected function createTestElasticIp($num) {
    $timestamp = time();
    $public_ip = Utils::getRandomPrivateIp();

    $elastic_ip = ElasticIp::create([
      'cloud_context' => $this->cloudContext,
      'name' => "Elastic IP #$num - " . date('Y/m/d - ') . $this->random->name(15, TRUE),
      'public_ip' => $public_ip,
      'instance_id' => NULL,
      'network_interface_id' => NULL,
      'private_ip_address' => NULL,
      'network_interface_owner' => NULL,
      'allocation_id' => NULL,
      'association_id' => NULL,
      'domain' => NULL,
      'created' => $timestamp,
      'changed' => $timestamp,
      'refreshed' => $timestamp,
    ]);
    $elastic_ip->save();
    return $elastic_ip;
  }

  /**
   * Create snapshot test entity.
   *
   * @param string $snapshot_id
   *   Snapshot id.
   * @param string $snapshot_name
   *   Snapshot name.
   * @param string $cloud_context
   *   Cloud context.
   *
   * @return \Drupal\aws_cloud\Entity\Ec2\Snapshot
   *   The snapshot entity.
   */
  protected function createTestSnapshot($snapshot_id, $snapshot_name, $cloud_context) {
    $entity = Snapshot::create([
      'snapshot_id' => $snapshot_id,
      'name' => $snapshot_name,
      'cloud_context' => $cloud_context,
      'created' => time(),
    ]);
    $entity->save();
    return $entity;
  }

  /**
   * Create volume test entity.
   *
   * @param int $num
   *   Volume number.
   * @param string $volume_id
   *   Volume id.
   * @param string $name
   *   Volume name.
   * @param string $cloud_context
   *   Cloud context.
   * @param int $uid
   *   User id.
   *
   * @return \Drupal\aws_cloud\Entity\Ec2\Volume
   *   The volume entity.
   */
  protected function createTestVolume($num, $volume_id, $name, $cloud_context, $uid) {
    $entity = Volume::create([
      'name'              => $name,
      'size'              => $num * 10,
      'availability_zone' => "us-west-$num",
      'iops'              => $num * 1000,
      'encrypted'         => $num % 2,
      'volume_type'       => 'io1',
      'volume_id' => $volume_id,
      'cloud_context' => $cloud_context,
      'volume_status' => 'Available',
      'uid' => $uid,
      'state' => 'available',
    ]);
    $entity->save();
    return $entity;
  }

  /**
   * Initialize mock instance types.
   *
   * The mock instance types will be saved to configuration
   * aws_cloud_mock_instance_types.
   */
  protected function initMockInstanceTypes() {
    $mock_instance_types = [
      'm1.small' => 'm1.small:1:2:2:0.048:170:300',
      'm1.medium' => 'm1.medium:1:3:3.75:0.096:340:600',
      'm1.large' => 'm1.large:2:7:7.5:0.192:700:1200',
      't1.micro' => 't1.micro:1:2:2:0.048:170:300',
      't2.micro' => 't2.micro:1:2:2:0.048:170:300',
      't3.micro' => 't3.micro:1:2:2:0.048:170:300',
      'm1.xlarge' => 'm1.xlarge:2:7:7.5:0.384:1400:3000',
      'm2.xlarge' => 'm2.xlarge:2:7:7.5:0.766:2800:6000',
      'm3.xlarge' => 'm3.xlarge:2:7:7.5:1.5:6000:12000',
    ];

    $config = \Drupal::configFactory()->getEditable('aws_cloud.settings');
    $config->set('aws_cloud_mock_instance_types', json_encode($mock_instance_types))
      ->save();
  }

}
