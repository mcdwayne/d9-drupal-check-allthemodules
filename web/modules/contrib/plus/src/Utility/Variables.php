<?php

namespace Drupal\plus\Utility;

use Drupal\plus\Plugin\Theme\ThemeInterface;
use Drupal\plus\Plus;

/**
 * Class to help modify template variables.
 *
 * @ingroup utility
 */
class Variables extends DrupalArray {

  /**
   * An element object.
   *
   * @var \Drupal\plus\Utility\Element|FALSE
   */
  public $element = FALSE;

  /**
   * The active theme.
   *
   * @var \Drupal\plus\Plugin\Theme\ThemeInterface
   */
  protected $theme;

  /**
   * Element constructor.
   *
   * @param array $variables
   *   A theme hook variables array.
   * @param \Drupal\plus\Plugin\Theme\ThemeInterface $theme
   *   The current theme context for these Variables. If not passed, the active
   *   theme will be used.
   */
  public function __construct(array &$variables, ThemeInterface $theme = NULL) {
    if (!isset($theme)) {
      $theme = Plus::getActiveTheme();
    }
    $this->theme = $theme;
    $this->array = &$variables;
    if (isset($variables['element']) && Element::isRenderArray($variables['element'])) {
      $this->element = Element::reference($variables['element'], NULL, $theme);
    }
    elseif (isset($variables['elements']) && Element::isRenderArray($variables['elements'])) {
      $this->element = Element::reference($variables['elements'], NULL, $theme);
    }
  }

  /**
   * Creates a new \Drupal\plus\Utility\Variables instance.
   *
   * @param array $variables
   *   A theme hook variables array.
   * @param \Drupal\plus\Plugin\Theme\ThemeInterface $theme
   *   The theme that these variables belong to.
   *
   * @return \Drupal\plus\Utility\Variables
   *   The newly created Variables instance.
   */
  public static function create(array &$variables, ThemeInterface $theme = NULL) {
    return $variables instanceof self ? $variables : new self($variables, $theme);
  }

  /**
   * Retrieves a context value from the variables array or its element, if any.
   *
   * @param string $name
   *   The name of the context key to retrieve.
   * @param mixed $default
   *   Optional. The default value to use if the context $name isn't set.
   *
   * @return mixed|NULL
   *   The context value or the $default value if not set.
   */
  public function &getContext($name, $default = NULL) {
    $context = &$this->offsetGet($this->propertyPrefix . 'context', []);
    if (!isset($context[$name])) {
      // If there is no context on the variables array but there is an element
      // present, proxy the method to the element.
      if ($this->element) {
        return $this->element->getContext($name, $default);
      }
      $context[$name] = $default;
    }
    return $context[$name];
  }

  /**
   * Maps an element's properties to the variables attributes array.
   *
   * @param array $map
   *   An associative array whose keys are element property names and whose
   *   values are the variable names to set in the variables array; e.g.,
   *   array('#property_name' => 'variable_name'). If both names are identical
   *   except for the leading '#', then an attribute name value is sufficient
   *   and no property name needs to be specified.
   * @param bool $overwrite
   *   If the variable exists, it will be overwritten. This does not apply to
   *   attribute arrays, they will always be merged recursively.
   *
   * @return $this
   */
  public function map(array $map, $overwrite = TRUE) {
    // Immediately return if there is no element in the variable array.
    if (!$this->element) {
      return $this;
    }

    // Iterate over each map item.
    foreach ($map as $property => $variable) {
      // If the key is numeric, the attribute name needs to be taken over.
      if (is_int($property)) {
        $property = $variable;
      }

      // Merge attributes from the element.
      if (strpos($property, 'attributes') !== FALSE) {
        $this->setAttributes($this->element->getAttributes($property)->getArrayCopy(), $variable);
      }
      // Set normal variable.
      elseif ($overwrite || !$this->offsetExists($variable)) {
        $this->offsetSet($variable, $this->element->getProperty($property));
      }
    }
    return $this;
  }

}
