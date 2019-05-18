<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a SecurityGroup entity.
 *
 * @ingroup aws_cloud
 */
interface SecurityGroupInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * {@inheritdoc}
   */
  public function getCloudContext();

  /**
   * {@inheritdoc}
   */
  public function getDescription();

  /**
   * {@inheritdoc}
   */
  public function getGroupId();

  /**
   * {@inheritdoc}
   */
  public function setGroupId($group_id = '');

  /**
   * {@inheritdoc}
   */
  public function getGroupName();

  /**
   * {@inheritdoc}
   */
  public function getGroupDescription();

  /**
   * {@inheritdoc}
   */
  public function getVpcId();

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
  public function getIpPermission();

  /**
   * {@inheritdoc}
   */
  public function getOutboundPermission();

  /**
   * {@inheritdoc}
   */
  public function isDefaultVpc();

  /**
   * {@inheritdoc}
   */
  public function setDefaultVpc($default);

  /**
   * {@inheritdoc}
   */
  public function setRefreshed($time);

}
