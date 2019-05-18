<?php

namespace Drupal\drupal_yext\YextContent;

/**
 * An invalid source record.
 */
class YextIgnoreSourceRecord implements NodeMigrateSourceInterface {

  /**
   * {@inheritdoc}
   */
  public function getBio() : string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getGeo() : array {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function getCustom(string $id) : string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getHeadshot() : string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getName() : string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getYextId() : string {
    return '';
  }

  /**
   * {@inheritdoc}
   */
  public function getYextLastUpdate() : int {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function getYextRawData() : string {
    return '';
  }

}
