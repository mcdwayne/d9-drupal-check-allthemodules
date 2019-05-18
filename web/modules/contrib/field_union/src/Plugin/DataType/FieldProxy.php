<?php

namespace Drupal\field_union\Plugin\DataType;

use Drupal\Core\TypedData\Exception\MissingDataException;
use Drupal\Core\TypedData\Exception\ReadOnlyException;
use Drupal\Core\TypedData\Plugin\DataType\Map;
use Drupal\field_union\Plugin\Field\FieldType\FieldUnion;
use Drupal\field_union\TypedData\FieldProxyInterface;
use Drupal\field_union\TypedData\ProxyProperty;

/**
 * Defines a class for a field union field proxy.
 *
 * @DataType(
 *   id = "field_union_field_proxy",
 *   label = @Translation("Field proxy"),
 *   definition_class = "\Drupal\field_union\TypedData\FieldProxyDataDefinition"
 * )
 */
class FieldProxy extends Map implements FieldProxyInterface {

  /**
   * Parent item.
   *
   * @var \Drupal\Core\TypedData\Plugin\DataType\Map
   */
  protected $parent;

  /**
   * Fields.
   *
   * @var \Drupal\Core\TypedData\TypedDataInterface
   */
  protected $field;

  /**
   * Proxied properties.
   *
   * @var \Drupal\field_union\TypedData\ProxyProperty
   */
  protected $proxy;

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if (isset($this->proxy)) {
      return $this->proxy;
    }
    $this->proxy = new ProxyProperty($this, $this->getFieldProxy());
    return $this->proxy;
  }

  /**
   * Gets field proxy.
   *
   * @param mixed $values
   *   Values.
   *
   * @return \Drupal\Core\Field\FieldItemBase
   *   Field item.
   */
  protected function getFieldProxy($values = NULL) {
    if (!$this->field) {
      $this->field = \Drupal::service('typed_data_manager')->getPropertyInstance($this, 'proxy', $values);
    }
    return $this->field;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($values, $notify = TRUE) {
    if (is_scalar($values)) {
      $values = [$this->definition->getMainPropertyName() => $values];
    }
    $this->getFieldProxy(NULL)->setValue($values);
    $values = $this->field->getValue();
    parent::setValue($values, $notify);
    if ($values === NULL) {
      return;
    }
    foreach ($values as $key => $value) {
      try {
        $name = $this->getName() . FieldUnion::SEPARATOR . $key;
        if ($field = $this->getParent()->get($name)) {
          $field->setValue($value, $notify);
        }
      }
      catch (MissingDataException $e) {
        unset($values[$key]);
      }
      catch (ReadOnlyException $e) {
        unset($values[$key]);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $this->getFieldProxy()->preSave();
    $this->setValue($this->getFieldProxy()->getValue(), FALSE);
  }

  /**
   * {@inheritdoc}
   */
  public function __get($name) {
    return $this->getParent()->get($this->getName() . FieldUnion::SEPARATOR . $name)->getValue();
  }

  /**
   * {@inheritdoc}
   */
  public function getLangcode() {
    return $this->getParent()->getLangcode();
  }

  /**
   * {@inheritdoc}
   */
  public function set($property_name, $value, $notify = TRUE) {
    $this->getFieldProxy()->get($property_name)->setValue($value, $notify);
    $name = $this->getName() . FieldUnion::SEPARATOR . $property_name;
    if ($field = $this->getParent()->get($name)) {
      $field->setValue($value, $notify);
    }
    return parent::set($property_name, $value, $notify);
  }

  /**
   * {@inheritdoc}
   */
  public function __unset($name) {
    $name = $this->getName() . FieldUnion::SEPARATOR . $name;
    $this->getParent()->__unset($name);
    $this->getFieldProxy()->__unset($name);
  }

  /**
   * {@inheritdoc}
   */
  public function __set($name, $value) {
    $name = $this->getName() . FieldUnion::SEPARATOR . $name;
    if ($field = $this->getParent()->get($name)) {
      $this->getParent()->__set($name, $value);
    }
    $this->getFieldProxy()->__set($name, $value);
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return $this->getFieldProxy()->isEmpty();
  }

}
