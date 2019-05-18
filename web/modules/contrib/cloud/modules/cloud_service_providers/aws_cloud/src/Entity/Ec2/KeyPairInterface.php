<?php

namespace Drupal\aws_cloud\Entity\Ec2;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a KeyPair entity.
 *
 * @ingroup aws_cloud
 */
interface KeyPairInterface extends ContentEntityInterface, EntityOwnerInterface {

  /**
   * {@inheritdoc}
   */
  public function getCloudContext();

  /**
   * {@inheritdoc}
   */
  public function getKeyPairName();

  /**
   * {@inheritdoc}
   */
  public function getKeyFingerprint();

  /**
   * {@inheritdoc}
   */
  public function setKeyFingerprint($key_finterprint = '');

  /**
   * {@inheritdoc}
   */
  public function getKeyMaterial();

  /**
   * {@inheritdoc}
   */
  public function setKeyMaterial($key_material = '');

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
  public function getKeyFileLocation();

  /**
   * {@inheritdoc}
   */
  public function getKeyFileName();

  /**
   * {@inheritdoc}
   */
  public function saveKeyFile($key);

}
