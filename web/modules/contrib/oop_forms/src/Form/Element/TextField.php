<?php

namespace Drupal\oop_forms\Form\Element;


class TextField extends TextElement {

  /**
   * @var string
   */
  protected $autocompleteRouteName;

  /**
   * @var array
   */
  protected $autocompleteRouteParameters;

  /**
   * TextField constructor.
   */
  public function __construct() {
    return parent::__construct('textfield');
  }

  /**
   * {@inheritdoc}.
   */
  public function build() {
    $form = parent::build();

    Element::addParameter($form, 'autocomplete_route_name', $this->autocompleteRouteName);
    Element::addParameter($form, 'autocomplete_route_parameters', $this->autocompleteRouteParameters);

    return $form;
  }

  /**
   * @return string
   */
  public function getAutocompleteRouteName() {
    return $this->autocompleteRouteName;
  }

  /**
   * @param string $autocompleteRouteName
   *
   * @return TextField
   */
  public function setAutocompleteRouteName($autocompleteRouteName) {
    $this->autocompleteRouteName = $autocompleteRouteName;

    return $this;
  }

  /**
   * @return array
   */
  public function getAutocompleteRouteParameters() {
    return $this->autocompleteRouteParameters;
  }

  /**
   * @param array $autocompleteRouteParameters
   *
   * @return TextField
   */
  public function setAutocompleteRouteParameters($autocompleteRouteParameters) {
    $this->autocompleteRouteParameters = $autocompleteRouteParameters;

    return $this;
  }


}
