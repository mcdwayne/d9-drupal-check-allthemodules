<?php

namespace Drupal\field_union\TypedData;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\field_union\Plugin\DataType\FieldProxy;

/**
 * Defines a class for a proxy property.
 */
class ProxyProperty {

  /**
   * Values.
   *
   * @var array
   */
  protected $parent;

  /**
   * Field item.
   *
   * @var \Drupal\Core\Field\FieldItemInterface
   */
  protected $item;

  /**
   * Constructs a new ProxyProperty.
   *
   * @param \Drupal\field_union\Plugin\DataType\FieldProxy $parent
   *   Field to proxy.
   * @param \Drupal\Core\Field\FieldItemInterface $item
   *   Field item decorated by the proxy.
   */
  public function __construct(FieldProxy $parent, FieldItemInterface $item) {
    $this->parent = $parent;
    $this->item = $item;
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    return $this->item->__get($name);
  }

  /**
   * {@inheritdoc}
   */
  public function __set($name, $value) {
    $this->parent->set($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function __unset($name) {
    $this->parent->set($name, NULL);
  }

}
