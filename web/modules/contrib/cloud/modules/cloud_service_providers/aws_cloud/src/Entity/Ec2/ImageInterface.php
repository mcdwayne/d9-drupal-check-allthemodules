<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an Image entity.
 *
 * @ingroup aws_cloud
 */
interface ImageInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * {@inheritdoc}
   */
  public function getCloudContext();

  /**
   * {@inheritdoc}
   */
  public function getImageId();

  /**
   * {@inheritdoc}
   */
  public function setImageId($image_id = '');

  /**
   * {@inheritdoc}
   */
  public function getInstanceId();

  /**
   * {@inheritdoc}
   */
  public function getStatus();

  /**
   * {@inheritdoc}
   */
  public function getArchitecture();

  /**
   * {@inheritdoc}
   */
  public function getVirtualizationType();

  /**
   * {@inheritdoc}
   */
  public function getRootDeviceName();

  /**
   * {@inheritdoc}
   */
  public function getRamdiskId();

  /**
   * {@inheritdoc}
   */
  public function getProductCode();

  /**
   * {@inheritdoc}
   */
  public function getAmiName();

  /**
   * {@inheritdoc}
   */
  public function getSource();

  /**
   * {@inheritdoc}
   */
  public function getStateReason();

  /**
   * {@inheritdoc}
   */
  public function getPlatform();

  /**
   * {@inheritdoc}
   */
  public function getImageType();

  /**
   * {@inheritdoc}
   */
  public function getDescription();

  /**
   * {@inheritdoc}
   */
  public function getRootDeviceType();

  /**
   * {@inheritdoc}
   */
  public function getKernelId();

  /**
   * {@inheritdoc}
   */
  public function getBlockDevices();

  /**
   * {@inheritdoc}
   */
  public function getAccountId();

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
  public function setAccountId($account_id);

  /**
   * {@inheritdoc}
   */
  public function setName($name);

  /**
   * {@inheritdoc}
   */
  public function setStatus($status);

  /**
   * {@inheritdoc}
   */
  public function setVisibility($visibility);

}
