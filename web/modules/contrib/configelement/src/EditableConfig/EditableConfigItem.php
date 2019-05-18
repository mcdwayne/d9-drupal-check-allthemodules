<?php

namespace Drupal\configelement\EditableConfig;

use Drupal\Core\Config\Schema\TypedConfigInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Class EditableConfigItem
 *
 * Wraps an editable config item, which is a sub-array of a config object's data array.
 * It can
 * - care that the concerned config object are autosaved (if changed) only once
 *   (even if different config items share the same config object).
 * - validate the concerned config objects via typed data validations.
 *
 * @package Drupal\configelement\EditableConfig
 */
class EditableConfigItem implements EditableConfigItemInterface {

  use StringTranslationTrait;

  /** @var EditableConfigWrapperInterface */
  protected $editableConfigWrapper;

  /** @var string */
  protected $key;

  /** @var \Drupal\Core\TypedData\TypedDataInterface */
  protected $schemaWrapper;

  /**
   * EditableConfigItem constructor.
   *
   * @internal Use EditableConfigItemFactory::get
   *
   * @param EditableConfigWrapperInterface $editableConfigWrapper
   * @param string $key
   *
   * @throws \InvalidArgumentException
   *   If a config or key does not have a schema..
   */
  public function __construct(EditableConfigWrapperInterface $editableConfigWrapper, $key) {
    $this->editableConfigWrapper = $editableConfigWrapper;
    $this->key = $key;
    // Do this now to throw an illegal argument exception early.
    $this->prepareSchemaWrapper();
  }

  /**
   * @param \Drupal\configelement\EditableConfig\EditableConfigWrapperInterface $editableConfigWrapper
   * @param $key
   *
   * @return \Drupal\configelement\EditableConfig\EditableConfigItem
   */
  public static function create(EditableConfigWrapperInterface $editableConfigWrapper, $key) {
    return new static($editableConfigWrapper, $key);
  }

  /**
   * Get name.
   *
   * @return string
   */
  public function getName() {
    return $this->schemaWrapper->getName();
  }

  /**
   * Get label.
   *
   * @return string
   */
  public function getLabel() {
    return $this->schemaWrapper->getDataDefinition()->getLabel();
  }

  /**
   * Get schema class.
   *
   * @return string
   */
  public function getSchemaClass() {
    return get_class($this->schemaWrapper);
  }

  /**
   * Get form element.
   *
   * @return string
   */
  public function getFormElementType() {
    $dataDefinition = $this->schemaWrapper->getDataDefinition();
    return isset($dataDefinition['configelement_form_element']) ?
      $dataDefinition['configelement_form_element'] : NULL;
  }

  /**
   * Set a value.
   *
   * @param mixed $value
   */
  public function setValue($value) {
    $this->editableConfigWrapper->set($this->key, $value);
  }

  /**
   * Get value.
   *
   * @return mixed
   */
  public function getValue() {
    return $this->editableConfigWrapper->get($this->key);
  }

  /**
   * Add this as a cacheable dependency.
   *
   * @param array $element
   *   The render element.
   */
  public function addCachableDependencyTo(array &$element) {
    $this->editableConfigWrapper->addCachableDependencyTo($element);
  }

  /**
   * Get schema wrapper.
   *
   * @return \Drupal\Core\TypedData\TypedDataInterface
   *
   * @throws \InvalidArgumentException
   *   If a config or key does not have a schema..
   */
  protected function prepareSchemaWrapper() {
    $schemaWrapper = $this->editableConfigWrapper->getSchemaWrapper();
    if (!$this->key) {
      // @see \Drupal\Core\Config\Schema\ArrayElement::get
      // @todo Upstream: ArrayElement::get should implement this.
      $this->schemaWrapper = $schemaWrapper;
    }
    else {
      $this->schemaWrapper = $schemaWrapper->get($this->key);
    }
  }

  /**
   * @param $name
   *
   * @return EditableConfigItemInterface
   */
  public function get($name) {
    return new EditableConfigItem($this->editableConfigWrapper, "$this->key.$name");
  }

  /**
   * Get children.
   *
   * @return EditableConfigItemInterface[]
   */
  public function getElements() {
    // Inlineing isList() here so the IDE can infer type.
    if ($this->schemaWrapper instanceof TypedConfigInterface) {
      $childSchemaWrappers = $this->schemaWrapper->getElements();
      $elements = [];
      foreach ($childSchemaWrappers as $name => $child) {
        $elements[$name] = $this->get($name);
      }
      return $elements;
    }
    return [];
  }

  /**
   * @return bool
   */
  public function isList() {
    return $this->schemaWrapper instanceof TypedConfigInterface;
  }

  /**
   * @inheritDoc
   */
  public function validate() {
    $this->schemaWrapper->setValue($this->editableConfigWrapper->get($this->key));
    return $this->schemaWrapper->validate();
  }

}
