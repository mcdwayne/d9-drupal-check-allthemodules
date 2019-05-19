<?php

namespace Drupal\wrappers_delight\Plugin\WrappersDelight\FieldType;

use Drupal\wrappers_delight\Annotation\WrappersDelight;
use Drupal\wrappers_delight\FieldItemWrapper;

/**
 * @WrappersDelight(
 *   id = "field_type:link",
 *   type = WrappersDelight::TYPE_FIELD_TYPE,
 *   field_type = "link",
 * )
 */
class LinkItemWrapper extends FieldItemWrapper {

  /**
   * @return \Drupal\Core\TypedData\Plugin\DataType\Uri|NULL
   */
  public function getUri() {
    if (!$this->item->isEmpty()) {
      return $this->item->get('uri');
    }
    return NULL;
  }

  /**
   * @return \Drupal\Core\TypedData\Plugin\DataType\StringData|NULL
   */
  public function getTitle() {
    if (!$this->item->isEmpty()) {
      return $this->item->get('title');
    }
    return NULL;
  }

  /**
   * @return \Drupal\Core\TypedData\Plugin\DataType\Map|NULL
   */
  public function getOptions() {
    if (!$this->item->isEmpty()) {
      return $this->item->get('options');
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function isExternal() {
    if (!$this->item->isEmpty()) {
      return $this->item->isExternal();
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function getUrl() {
    if (!$this->item->isEmpty()) {
      return $this->item->getUrl();
    }
    return NULL;
  }

  /**
   * @return \Drupal\Core\Link|NULL
   */
  public function getLink() {
    if (!$this->item->isEmpty()) {
      $text = (!is_null($this->getTitle())) ? $this->getTitle() : $this->getUri();
      return \Drupal\Core\Link::fromTextAndUrl($text, $this->getUrl());
    }
    return NULL;
  }

  /**
   * @return \Drupal\link\LinkItemInterface
   */
  public function raw() {
    return parent::raw();
  }

}
