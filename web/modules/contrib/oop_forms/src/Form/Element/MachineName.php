<?php

namespace Drupal\oop_forms\Form\Element;

/**
 * Class MachineName
 * Provides a machine name element.
 *
 */
class MachineName extends Element {

  /**
   * Machine name settings
   *
   * @var array
   */
  protected $machineName;

  /**
   * Item constructor.
   *
   */
  public function __construct() {
    return parent::__construct('machine_name');
  }

  /**
   * Gets machine name settings.
   *
   * @return array|string
   */
  public function getMachineName() {
    return $this->machineName;
  }

  /**
   * Sets machine name settings.
   *
   * When only one parameter is passed it overrides the whole machine name
   * property for the element. Otherwise - only the given key is overridden.
   *
   * @param array|string $machineName
   * @param string|null $value
   *
   * @return MachineName
   */
  public function setMachineName($machineName, $value = NULL) {
    if (!is_array($machineName) && $value) {
      $this->machineName[$machineName] = $value;
    }
    else {
      $this->machineName = $machineName;
    }

    return $this;
  }

  /**
   * {@inheritdoc}.
   */
  public function build() {
    $element = parent::build();

    Element::addParameter($element, 'machine_name', $this->machineName);

    return $element;
  }


}
