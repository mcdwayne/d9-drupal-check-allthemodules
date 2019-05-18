<?php

namespace Drupal\scss_field;

use Drupal\Core\TypedData\TypedData;
use Drupal\Core\TypedData\TypedDataInterface;
use Leafo\ScssPhp\Compiler;

/**
 * A computed property for compiling SCSS into CSS.
 */
class ScssCompiled extends TypedData {

  /**
   * Cached compiled CSS.
   *
   * @var string|null
   */
  protected $compiled = NULL;

  /**
   * The SCSS compiler.
   *
   * @var \Leafo\ScssPhp\Compiler
   */
  protected $compiler;

  /**
   * {@inheritdoc}
   */
  public function __construct($definition, $name = NULL, TypedDataInterface $parent = NULL) {
    parent::__construct($definition, $name, $parent);
    $this->compiler = new Compiler();
  }

  /**
   * {@inheritdoc}
   *
   * @var string $scope
   *   a CSS selector to wrap around the SCSS; for scoping purposes.
   */
  public function getValue() {
    if ($this->compiled !== NULL) {
      return $this->compiled;
    }

    $item = $this->getParent();
    $value = $item->get('value')->getValue();
    if ($item->getParent()->getSetting('scoped')) {
      $entity = $item->getEntity();
      $entity_id = $entity->id();
      $entity_type_id = $entity->getEntityTypeId();
      $value = "[data-scssfield-$entity_type_id-$entity_id] { $value }";
    }
    $this->compiled = $this->compiler->compile($value);
    return $this->compiled;
  }

  /**
   * {@inheritdoc}
   */
  public function setValue($value, $notify = TRUE) {
    $this->compiled = $value;
    // Notify the parent of any changes.
    if ($notify && isset($this->parent)) {
      $this->parent->onChange($this->name);
    }
  }

}
