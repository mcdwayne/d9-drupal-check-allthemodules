<?php

namespace Drupal\configelement\EditableConfig;

use Drupal\Core\StringTranslation\StringTranslationTrait;

class EditableConfigFallbackItem implements EditableConfigItemInterface {

  use StringTranslationTrait;

  /** @var \Drupal\configelement\EditableConfig\EditableConfigItemInterface */
  protected $editableConfigItem;

  /** @var \Drupal\configelement\EditableConfig\EditableConfigItemInterface */
  protected $editableConfigBaseItem;

  /**
   * EditableConfigFallbackItem constructor.
   *
   * @param \Drupal\configelement\EditableConfig\EditableConfigItemInterface $editableConfigItem
   * @param \Drupal\configelement\EditableConfig\EditableConfigItemInterface $editableConfigBaseItem
   */
  public function __construct(EditableConfigItemInterface $editableConfigItem, EditableConfigItemInterface $editableConfigBaseItem) {
    $this->editableConfigItem = $editableConfigItem;
    $this->editableConfigBaseItem = $editableConfigBaseItem;
  }

  /**
   * @param \Drupal\configelement\EditableConfig\EditableConfigItemInterface $item
   * @param \Drupal\configelement\EditableConfig\EditableConfigItemInterface $baseItem
   * @param $key
   *
   * @return \Drupal\configelement\EditableConfig\EditableConfigFallbackItem
   */
  public static function create(EditableConfigItemInterface $item, EditableConfigItemInterface $baseItem, $key) {
    return new static($item, $baseItem, $key);
  }

  public function getName() {
    return $this->editableConfigItem->getName();
  }

  public function getLabel() {
    return $this->t('@label (with fallback)', ['@label' => $this->editableConfigItem->getLabel()]);
  }

  public function getSchemaClass() {
    return $this->editableConfigBaseItem->getSchemaClass();
  }

  public function addCachableDependencyTo(array &$element) {
    $this->editableConfigBaseItem->addCachableDependencyTo($element);
  }

  public function get($name) {
    $overriddenElements = $this->editableConfigItem->getElements();
    if (isset($overriddenElements[$name])) {
      return new static($this->editableConfigItem->get($name), $this->editableConfigBaseItem->get($name));
    }
    else {
      return $this->editableConfigBaseItem->get($name);
    }
  }

  public function getElements() {
    $baseElements = $this->editableConfigBaseItem->getElements();
    $elements = [];
    foreach ($baseElements as $key => $_) {
      $elements[$key] = $this->get($key);
    }
    return $elements;
  }

  public function isList() {
    return $this->editableConfigBaseItem->isList();
  }

  public function getFormElementType() {
    return $this->editableConfigBaseItem->getFormElementType();
  }

  public function getValue() {
    if ($this->isList()) {
      $value = [];
      foreach ($this->getElements() as $key => $item) {
        $value[$key] = $this->get($key)->getValue();
      }
    }
    else {
      $value = $this->editableConfigItem->getValue() ?:
        $this->editableConfigBaseItem->getValue();
    }
    return $value;
  }

  public function setValue($value) {
    if ($this->isList()) {
      foreach ($this->getElements() as $key => $item) {
        $item->setValue(isset($value[$key]) ? $value[$key] : NULL);
      }
    }
    else {
      if ($this->editableConfigBaseItem->getValue() === $value) {
        $this->editableConfigItem->setValue(NULL);
      }
      else {
        $this->editableConfigItem->setValue($value);
      }
    }

  }

  /**
   * @inheritDoc
   */
  public function validate() {
    return $this->editableConfigItem->validate();
  }

}
