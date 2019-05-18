<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining an Snapshot entity.
 *
 * @ingroup aws_cloud
 */
interface SnapshotInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * {@inheritdoc}
   */
  public function getCloudContext();

  /**
   * {@inheritdoc}
   */
  public function getSnapshotId();

  /**
   * {@inheritdoc}
   */
  public function setSnapshotId($snapshot_id = '');

  /**
   * {@inheritdoc}
   */
  public function getSize();

  /**
   * {@inheritdoc}
   */
  public function getDescription();

  /**
   * {@inheritdoc}
   */
  public function getStatus();

  /**
   * {@inheritdoc}
   */
  public function setStatus($status = 'unknown');

  /**
   * {@inheritdoc}
   */
  public function getStarted();

  /**
   * {@inheritdoc}
   */
  public function setStarted($started = 0);

  /**
   * {@inheritdoc}
   */
  public function getVolumeId();

  /**
   * {@inheritdoc}
   */
  public function getAccountId();

  /**
   * {@inheritdoc}
   */
  public function getOwnerAliases();

  /**
   * {@inheritdoc}
   */
  public function getEncrypted();

  /**
   * {@inheritdoc}
   */
  public function setEncrypted($encrypted = FALSE);

  /**
   * {@inheritdoc}
   */
  public function getKmsKeyId();

  /**
   * {@inheritdoc}
   */
  public function getStateMessage();

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
