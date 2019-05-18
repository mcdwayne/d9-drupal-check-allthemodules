<?php

namespace Drupal\js_management\Entity;

use Drupal\Core\Entity\ContentEntityInterface;

interface JavaScriptManagedInterface extends ContentEntityInterface {
  /**
   * {@inheritdoc}
   */
  public function getData();

  /**
   * {@inheritdoc}
   */
  public function setData($data);

  /**
   * {@inheritdoc}
   */
  public function getMinified();

  /**
   * {@inheritdoc}
   */
  public function setMinified($minified);

  /**
   * {@inheritdoc}
   */
  public function getVersion();

  /**
   * {@inheritdoc}
   */
  public function setVersion($version);

  /**
   * {@inheritdoc}
   */
  public function getType();

  /**
   * {@inheritdoc}
   */
  public function setType($type);

  public function getLoad();

  public function setLoad($load);
}
