<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an Volume entity.
 *
 * @ingroup aws_cloud
 */
interface VolumeInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * {@inheritdoc}
   */
  public function getCloudContext();

  /**
   * {@inheritdoc}
   */
  public function getVolumeId();

  /**
   * {@inheritdoc}
   */
  public function setVolumeId($volume_id = '');

  /**
   * {@inheritdoc}
   */
  public function getSize();

  /**
   * {@inheritdoc}
   */
  public function getState();

  /**
   * {@inheritdoc}
   */
  public function setState($state = '');

  /**
   * {@inheritdoc}
   */
  public function getVolumeStatus();

  /**
   * {@inheritdoc}
   */
  public function getAttachmentInformation();

  /**
   * {@inheritdoc}
   */
  public function getVolumeType();

  /**
   * {@inheritdoc}
   */
  public function getProductCodes();

  /**
   * {@inheritdoc}
   */
  public function getIops();

  /**
   * {@inheritdoc}
   */
  public function getAlarmStatus();

  /**
   * {@inheritdoc}
   */
  public function getSnapshotId();

  /**
   * {@inheritdoc}
   */
  public function setSnapshotId();

  /**
   * {@inheritdoc}
   */
  public function getSnapshotName();

  /**
   * {@inheritdoc}
   */
  public function setSnapshotName();

  /**
   * {@inheritdoc}
   */
  public function getAvailabilityZone();

  /**
   * {@inheritdoc}
   */
  public function getEncrypted();

  /**
   * {@inheritdoc}
   */
  public function getKmsKeyId();

  /**
   * {@inheritdoc}
   */
  public function getKmsKeyAliases();

  /**
   * {@inheritdoc}
   */
  public function getKmsKeyArn();

  /**
   * {@inheritdoc}
   */
  public function created();

  /**
   * {@inheritdoc}
   */
  public function setCreated($created = 0);

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
  public function isVolumeUnused();

}
