<?php

namespace Drupal\oop_forms\Form\Element;

/**
 * Provides an entity autocomplete form element.
 *
 * The #default_value accepted by this element is either an entity object or an array of entity objects.
 */
class EntityAutocomplete extends TextElement {

  /**
   * @var string
   */
  protected $selectionHandler = 'default';

  /**
   * Target entity type.
   *
   * @var string
   */
  protected $targetType;

  /**
   * Target bundles.
   *
   * @var string[]
   */
  protected $targetBundles = [];

  /**
   * EntityAutocomplete constructor.
   */
  public function __construct() {
    parent::__construct('entity_autocomplete');
  }

  /**
   * {@inheritdoc}.
   */
  public function build() {
    $form = parent::build();

    Element::addParameter($form, 'target_type', $this->targetType);
    Element::addParameter($form, 'selection_handler', $this->selectionHandler);

    if (!empty($this->targetBundles)) {
      Element::addParameter($form, 'selection_settings', ['target_bundles' => $this->targetBundles]);
    }

    return $form;
  }

  /**
   * @return string
   */
  public function getSelectionHandler() {
    return $this->selectionHandler;
  }

  /**
   * @param string $selectionHandler
   *
   * @return EntityAutocomplete
   */
  public function setSelectionHandler($selectionHandler) {
    $this->selectionHandler = $selectionHandler;

    return $this;
  }

  /**
   * @return string
   */
  public function getTargetType() {
    return $this->targetType;
  }

  /**
   * @param string $targetType
   *
   * @return EntityAutocomplete
   */
  public function setTargetType($targetType) {
    $this->targetType = $targetType;

    return $this;
  }

  /**
   * @return \string[]
   */
  public function getTargetBundles() {
    return $this->targetBundles;
  }

  /**
   * @param \string[] $targetBundles
   *
   * @return EntityAutocomplete
   */
  public function setTargetBundles($targetBundles) {
    $this->targetBundles = $targetBundles;

    return $this;
  }


}
