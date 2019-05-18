<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a NetworkInterface entity.
 *
 * @ingroup aws_cloud
 */
interface NetworkInterfaceInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * {@inheritdoc}
   */
  /*
  public function getCloudContext();
   */

  /**
   * {@inheritdoc}
   */
  public function getNetworkInterfaceId();

  /**
   * {@inheritdoc}
   */
  public function setNetworkInterfaceId($network_interface);

  /**
   * {@inheritdoc}
   */
  public function getVpcId();

  /**
   * {@inheritdoc}
   */
  public function setVpcId($vpc_id = '');

  /**
   * {@inheritdoc}
   */
  public function getMacAddress();

  /**
   * {@inheritdoc}
   */
  public function getSecurityGroups();

  /**
   * {@inheritdoc}
   */
  public function getStatus();

  /**
   * {@inheritdoc}
   */
  public function setStatus($status = '');

  /**
   * {@inheritdoc}
   */
  public function getPrivateDns();

  /**
   * {@inheritdoc}
   */
  public function getPrimaryPrivateIp();

  /**
   * {@inheritdoc}
   */
  public function getPrimary();

  /**
   * {@inheritdoc}
   */
  public function getSecondaryPrivateIps();

  /**
   * {@inheritdoc}
   */
  public function getAttachmentId();

  /**
   * {@inheritdoc}
   */
  public function getAttachmentOwner();

  /**
   * {@inheritdoc}
   */
  public function getAttachmentStatus();

  /**
   * {@inheritdoc}
   */
  public function getAccountId();

  /**
   * {@inheritdoc}
   */
  public function getAssociationId();

  /**
   * {@inheritdoc}
   */
  public function getSubnetId();

  /**
   * {@inheritdoc}
   */
  public function getAvailabilityZone();

  /**
   * {@inheritdoc}
   */
  public function getDescription();

  /**
   * {@inheritdoc}
   */
  public function getPublicIps();

  /**
   * {@inheritdoc}
   */
  public function getSourceDestCheck();

  /**
   * {@inheritdoc}
   */
  public function getInstanceId();

  /**
   * {@inheritdoc}
   */
  public function getDeviceIndex();

  /**
   * {@inheritdoc}
   */
  public function getDeleteOnTermination();

  /**
   * {@inheritdoc}
   */
  public function getAllocationId();

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
  public function setPrimaryPrivateIp($private_ip);

  /**
   * {@inheritdoc}
   */
  public function setSecondaryPrivateIp($secondary_ip);

  /**
   * {@inheritdoc}
   */
  public function setSecondaryAssociationId($association_id);

  /**
   * {@inheritdoc}
   */
  public function getSecondaryAssociationId();

  /**
   * {@inheritdoc}
   */
  public function setAssociationId($association_id);

  /**
   * {@inheritdoc}
   */
  public function setPublicIps($public_ips);

}
