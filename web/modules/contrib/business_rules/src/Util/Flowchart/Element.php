<?php

namespace Drupal\business_rules\Util\Flowchart;

use Drupal\Core\Entity\EntityInterface;

/**
 * Class Element.
 *
 * @package Drupal\business_rules\Util\Flowchart
 */
class Element {

  /**
   * The arrow label.
   *
   * @var string
   */
  protected $arrowLabel;

  /**
   * The Business Rule item.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $item;

  /**
   * The connector origin uuid.
   *
   * @var string
   */
  protected $originUuid;

  /**
   * The Item parent.
   *
   * @var \Drupal\business_rules\Util\Flowchart\Element
   */
  protected $parent;

  /**
   * The element uuid.
   *
   * @var string
   */
  protected $uuid;

  /**
   * Element constructor.
   *
   * @param \Drupal\Core\Entity\EntityInterface|null $item
   *   The entity.
   * @param \Drupal\business_rules\Util\Flowchart\Element|null $parent
   *   The parent entity.
   * @param string $originUUid
   *   The uuid for the origin element. The arrow beginning.
   * @param string $arrowLabel
   *   The arrow label.
   */
  public function __construct(EntityInterface $item = NULL, Element $parent = NULL, $originUUid = '', $arrowLabel = '') {
    $this->setItem($item);
    $this->parent = $parent;
    $this->setOriginUuid($originUUid);
    $this->setArrowLabel($arrowLabel);
    $this->uuid = \Drupal::service('uuid')->generate();
  }

  /**
   * Get the arrow label.
   *
   * @return string
   *   The label.
   */
  public function getArrowLabel() {
    return $this->arrowLabel;
  }

  /**
   * Set the arrow label.
   *
   * @param string $arrowLabel
   *   The label.
   */
  public function setArrowLabel($arrowLabel) {
    $this->arrowLabel = $arrowLabel;
  }

  /**
   * Get the item element.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The item.
   */
  public function getItem() {
    return $this->item;
  }

  /**
   * Set the item element.
   *
   * @param \Drupal\Core\Entity\EntityInterface $item
   *   The item.
   */
  public function setItem(EntityInterface $item) {
    $this->item = $item;
  }

  /**
   * Get the graph origin uuid.
   *
   * @return string
   *   The origin id.
   */
  public function getOriginUuid() {
    return $this->originUuid;
  }

  /**
   * Set the graph origin uuid.
   *
   * @param string $originUuid
   *   The graph origin uuid.
   */
  public function setOriginUuid($originUuid) {
    $this->originUuid = $originUuid;
  }

  /**
   * Get the parent item.
   *
   * @return \Drupal\business_rules\Util\Flowchart\Element
   *   The parent element.
   */
  public function getParent() {
    return $this->parent;
  }

  /**
   * Set the parent item.
   *
   * @param \Drupal\business_rules\Util\Flowchart\Element $parent
   *   The parent item.
   */
  public function setParent(Element $parent) {
    $this->parent = $parent;
  }

  /**
   * Get the element uuid.
   *
   * @return string
   *   The element uuid.
   */
  public function getUuid() {
    return $this->uuid;
  }

}
