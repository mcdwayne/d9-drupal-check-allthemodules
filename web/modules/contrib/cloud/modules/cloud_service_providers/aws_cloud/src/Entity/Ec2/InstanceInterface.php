<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an Instance entity.
 *
 * @ingroup aws_cloud
 */
interface InstanceInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * {@inheritdoc}
   */
  public function getCloudContext();

  /**
   * {@inheritdoc}
   */
  public function getInstanceId();

  /**
   * {@inheritdoc}
   */
  public function getInstanceType();

  /**
   * {@inheritdoc}
   */
  public function getAvailabilityZone();

  /**
   * {@inheritdoc}
   */
  public function getInstanceState();

  /**
   * {@inheritdoc}
   */
  public function getPublicDns();

  /**
   * {@inheritdoc}
   */
  public function setPublicDns($dns);

  /**
   * {@inheritdoc}
   */
  public function getPublicIp();

  /**
   * {@inheritdoc}
   */
  public function getElasticIp();

  /**
   * {@inheritdoc}
   */
  public function getPrivateDns();

  /**
   * {@inheritdoc}
   */
  public function setPrivateDns($private_dns);

  /**
   * {@inheritdoc}
   */
  public function getPrivateIps();

  /**
   * {@inheritdoc}
   */
  public function setPrivateIps($private_ips);

  /**
   * {@inheritdoc}
   */
  public function getPrivateSecondaryIp();

  /**
   * {@inheritdoc}
   */
  public function setPrivateSecondaryIp($private_ip);

  /**
   * {@inheritdoc}
   */
  public function getKeyPairName();

  /**
   * {@inheritdoc}
   */
  public function isMonitoring();

  /**
   * {@inheritdoc}
   */
  public function setMonitoring($is_monitoring);

  /**
   * {@inheritdoc}
   */
  public function getMonitoring();

  /**
   * {@inheritdoc}
   */
  public function setLaunchTime($launch_time);

  /**
   * {@inheritdoc}
   */
  public function getLaunchTime();

  /**
   * {@inheritdoc}
   */
  public function getSecurityGroups();

  /**
   * {@inheritdoc}
   */
  public function getVpcId();

  /**
   * {@inheritdoc}
   */
  public function getSubnetId();

  /**
   * {@inheritdoc}
   */
  public function getNetworkInterfaces();

  /**
   * {@inheritdoc}
   */
  public function getSourceDestCheck();

  /**
   * {@inheritdoc}
   */
  public function getEbsOptimized();

  /**
   * {@inheritdoc}
   */
  public function getRootDeviceType();

  /**
   * {@inheritdoc}
   */
  public function getRootDevice();

  /**
   * {@inheritdoc}
   */
  public function getBlockDevices();

  /**
   * {@inheritdoc}
   */
  public function getScheduledEvents();

  /**
   * {@inheritdoc}
   */
  public function getImageId();

  /**
   * {@inheritdoc}
   */
  public function getPlatform();

  /**
   * {@inheritdoc}
   */
  public function getIamRole();

  /**
   * {@inheritdoc}
   */
  public function setIamRole($iam_role);

  /**
   * {@inheritdoc}
   */
  public function getTerminationProtection();

  /**
   * {@inheritdoc}
   */
  public function setTerminationProtection($termination_protection);

  /**
   * {@inheritdoc}
   */
  public function getLifecycle();

  /**
   * {@inheritdoc}
   */
  public function getAlarmStatus();

  /**
   * {@inheritdoc}
   */
  public function getKernelId();

  /**
   * {@inheritdoc}
   */
  public function getRamdiskId();

  /**
   * {@inheritdoc}
   */
  public function getPlacementGroup();

  /**
   * {@inheritdoc}
   */
  public function getVirtualization();

  /**
   * {@inheritdoc}
   */
  public function getReservation();

  /**
   * {@inheritdoc}
   */
  public function getAmiLaunchIndex();

  /**
   * {@inheritdoc}
   */
  public function getTenancy();

  /**
   * {@inheritdoc}
   */
  public function getHostId();

  /**
   * {@inheritdoc}
   */
  public function getAffinity();

  /**
   * {@inheritdoc}
   */
  public function getStateTransitionReason();

  /**
   * {@inheritdoc}
   */
  public function getLoginUsername();

  /**
   * {@inheritdoc}
   */
  public function getCloudType();

  /**
   * {@inheritdoc}
   */
  public function getUserData();

  /**
   * {@inheritdoc}
   */
  public function getMinCount();

  /**
   * {@inheritdoc}
   */
  public function getMaxCount();

  /**
   * {@inheritdoc}
   */
  public function setInstanceId($instance_id = '');

  /**
   * {@inheritdoc}
   */
  public function setInstanceState($state = '');

  /**
   * {@inheritdoc}
   */
  public function setElasticIp($elastic_ip = '');

  /**
   * {@inheritdoc}
   */
  public function setPublicIp($public_ip = '');

  /**
   * {@inheritdoc}
   */
  public function created();

  /**
   * {@inheritdoc}
   */
  public function changed();

  /**
   * {@inheritdoc}
   */
  public function getRefreshed();

  /**
   * {@inheritdoc}
   */
  public function setRefreshed($time);

  /**
   * {@inheritdoc}
   */
  public function setName($name);

  /**
   * {@inheritdoc}
   */
  public function getCost();

  /**
   * {@inheritdoc}
   */
  public function setCost($cost);

  /**
   * Load an instance entity by the aws instance_id.
   *
   * @param string $instance_id
   *   The ID of instance.
   *
   * @return \Drupal\aws_cloud\Entity\Ec2\Instance
   *   The Instance Entity.
   */
  public static function loadByInstanceId($instance_id);

  /**
   * Get the termination timestamp.
   */
  public function getTerminationTimestamp();

  /**
   * Set the termination timestamp.
   *
   * @param int $timestamp
   *   The UNIX timestamp of termination.
   *
   * @return \Drupal\aws_cloud\Entity\Ec2\Instance
   *   The Instance Entity.
   */
  public function setTerminationTimestamp($timestamp);

  /**
   * Get the tags.
   */
  public function getTags();

  /**
   * Set the tags.
   *
   * @param array $tags
   *   The tags.
   *
   * @return \Drupal\aws_cloud\Entity\Ec2\Instance
   *   The Instance Entity.
   */
  public function setTags(array $tags);

  /**
   * Set network interfaces.
   *
   * @param array $interfaces
   *   An array of Interfaces.
   */
  public function setNetworkInterfaces(array $interfaces);

}
