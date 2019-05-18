<?php

namespace Drupal\entity_access_audit\Dimensions;

use Drupal\Core\Entity\EntityInterface;
use Drupal\entity_access_audit\AccessDimensionInterface;

/**
 * Dimension for entity bundles.
 */
class BundleDimension implements AccessDimensionInterface {

  /**
   * The bundle entity type.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $bundle;

  /**
   * BundleDimension constructor.
   */
  public function __construct(EntityInterface $bundleEntity) {
    $this->bundle = $bundleEntity;
  }

  /**
   * {@inheritdoc}
   */
  public static function getLabel() {
    return t('Entity bundle');
  }

  /**
   * {@inheritdoc}
   */
  public function getDimensionValue() {
    return $this->bundle->label();
  }

  /**
   * Get the bundle ID.
   *
   * @return string
   *   The bundle ID.
   */
  public function getBundleId() {
    return $this->bundle->id();
  }

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->bundle->id();
  }

}
