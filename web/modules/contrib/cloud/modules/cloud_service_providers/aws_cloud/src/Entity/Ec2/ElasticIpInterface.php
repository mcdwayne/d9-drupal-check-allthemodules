<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an ElasticIp entity.
 *
 * @ingroup aws_cloud
 */
interface ElasticIpInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * {@inheritdoc}
   */
  public function getCloudContext();

  /**
   * {@inheritdoc}
   */
  public function getPublicIp();

  /**
   * {@inheritdoc}
   */
  public function setPublicIp($public_ip = '');

  /**
   * {@inheritdoc}
   */
  public function setAllocationId($allocation_id = '');

  /**
   * {@inheritdoc}
   */
  public function setAssociationId($association_id = '');

  /**
   * {@inheritdoc}
   */
  public function getInstanceId();

  /**
   * {@inheritdoc}
   */
  public function getDomain();

  /**
   * {@inheritdoc}
   */
  public function getScope();

  /**
   * {@inheritdoc}
   */
  public function getNetworkInterfaceId();

  /**
   * {@inheritdoc}
   */
  public function getPrivateIpAddress();

  /**
   * {@inheritdoc}
   */
  public function getNetworkInterfaceOwner();

  /**
   * {@inheritdoc}
   */
  public function getAllocationId();

  /**
   * {@inheritdoc}
   */
  public function getAssociationId();

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

}
