<?php

namespace Drupal\field_nif;

use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\Core\TypedData\TypedData;
use Drupal\Core\TypedData\TypedDataInterface;

/**
 * A computed property for processing NIF/CIF/NIE documents.
 *
 * Required settings (below the definition's 'settings' key) are:
 *  - document source: The properties containing the to be processed document.
 */
class NifProcessed extends TypedData {

  /**
   * Cached processed document.
   *
   * @var string|null
   */
  protected $processed = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(DataDefinitionInterface $definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);

    if ($definition->getSetting('document source') === NULL) {
      throw new \InvalidArgumentException("The definition's 'document source' key has to specify the name of the properties to be processed.");
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getValue() {
    if ($this->processed !== NULL) {
      return $this->processed;
    }

    $this->processed = '';

    $item = $this->getParent();
    $source = $this->definition->getSetting('document source');

    foreach ($source as $property) {
      $this->processed .= $item->{$property};
    }

    return $this->processed;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->processed = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
