<?php

namespace Drupal\plus\Utility;

use Drupal\Core\Render\RenderContext;
use Drupal\plus\Plugin\Theme\ThemeInterface;
use Drupal\plus\Traits\RendererTrait;
use Drupal\plus\Plus;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element as CoreElement;

/**
 * Provides helper methods for Drupal render elements.
 *
 * @ingroup utility
 *
 * @see \Drupal\Core\Render\Element
 */
class Element extends DrupalArray {

  use RendererTrait;

  /**
   * The current state of the form.
   *
   * @var \Drupal\Core\Form\FormStateInterface
   */
  protected $formState;

  /**
   * The element type.
   *
   * @var string
   */
  protected $type = FALSE;

  /**
   * {@inheritdoc}
   */
  protected $propertyPrefix = '#';

  /**
   * The current theme.
   *
   * @var \Drupal\plus\Plugin\Theme\ThemeInterface
   */
  protected $theme;

  /** @noinspection PhpMissingParentConstructorInspection */

  /**
   * {@inheritdoc}
   */
  public function __construct(&$element = [], FormStateInterface $form_state = NULL, ThemeInterface $theme = NULL) {
    if (!isset($theme)) {
      $theme = Plus::getActiveTheme();
    }
    $this->theme = $theme;

    if (!is_array($element)) {
      $element = ['#markup' => $element instanceof MarkupInterface ? $element : new FormattableMarkup((string) $element, [])];
    }
    $this->__storage = &$element;
    $this->formState = $form_state;
  }

  /**
   * Implements the magic __toString() method.
   *
   * Note: this mimics ToStringTrait, but because this may be invoked outside
   * of any RenderContext, it should use ::renderPlain instead of the trait's
   * normal ::render method invocation.
   *
   * @see \Drupal\Component\Utility\ToStringTrait::__toString
   */
  public function __toString() {
    try {
      return (string) $this->renderPlain();
    }
    catch (\Exception $e) {
      // User errors in __toString() methods are considered fatal in the Drupal
      // error handler.
      trigger_error(get_class($e) . ' thrown while calling __toString on a ' . get_class($this) . ' object in ' . $e->getFile() . ' on line ' . $e->getLine() . ': ' . $e->getMessage(), E_USER_ERROR);
      // In case there is a different error handler being used that did not
      // fatal on E_USER_ERROR, terminate the PHP script execution. However,
      // for test purposes allow a return value.
      return $this->_die();
    }
  }

  /**
   * For test purposes, wrap die() in an overridable method.
   */
  protected function _die() { // @codingStandardsIgnoreLine
    die();
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\plus\Utility\Element
   *   The newly created element instance.
   */
  public static function create(...$arguments) {
    foreach ([0, 1, 2] as $i) {
      if (!isset($arguments[$i])) {
        $arguments[$i] = $i === 0 ? [] : NULL;
      }
    }

    // Immediately return a cloned version if element is already an Element.
    $element = $arguments[0];
    if ($element instanceof self) {
      $clone = $element->copy();
    }
    elseif (is_object($element)) {
      $clone = clone $element;
    }
    else {
      $clone = $element;
    }

    $class = static::getElementClass();
    return new $class($clone, $arguments[1], $arguments[2]);
  }

  /**
   * {@inheritdoc}
   *
   * @return \Drupal\plus\Utility\Element
   *   The newly created element instance.
   */
  public static function reference(&...$arguments) {
    foreach ([0, 1, 2] as $i) {
      if (!isset($arguments[$i])) {
        $arguments[$i] = $i === 0 ? [] : NULL;
      }
    }

    // Immediately return if already an Element instance.
    $element = &$arguments[0];
    if ($element instanceof self) {
      return $element;
    }

    $class = static::getElementClass();
    return new $class($element, $arguments[1], $arguments[2]);
  }

  /**
   * Adds a callback to an array.
   *
   * @param string $property
   *   The name of the element property to add callback to, no # prefix.
   * @param callable $callback
   *   The callback to add.
   * @param array|string $replace
   *   If specified, the callback will instead replace the specified value
   *   instead of being appended to the $callbacks array.
   * @param string $placement
   *   Flag that determines how to add the callback to the array.
   * @param bool $default_info
   *   Flag indicating whether to merge in the default element info.
   *
   * @return bool
   *   TRUE if the callback was added, FALSE if $replace was specified but its
   *   callback could be found in the list of callbacks.
   *
   * @throws \InvalidArgumentException
   *   If $property contains a # prefix.
   *   If $placement is not a valid type.
   */
  public function addCallback($property, callable $callback, $replace = NULL, $placement = 'append', $default_info = TRUE) {
    // Ensure that the property name does not have a # prefix.
    if (CoreElement::property($property)) {
      throw new \InvalidArgumentException('Property name must not include a # prefix.');
    }

    // Before Drupal 8, most render array callbacks were invoked manually, not
    // using call_user_func_array(), which makes it impossible to add static
    // method callbacks from classes. Instead, this must specify a procedural
    // function that correlates with the type of callback.
    if ((int) \Drupal::VERSION[0] < 8) {
      $element_callbacks = &$this->getProperty("plus_$property", []);
      $element_callbacks[] = $callback;
      $callback = 'plus_element_' . $property . '_callback';
    }

    // Only continue if callback is valid.
    if (!is_callable($callback)) {
      throw new \InvalidArgumentException(sprintf('Unknown callback: %s', is_array($callback) ? '[' . implode(', ', $callback) . ']' : (string) $callback));
    }

    // Retrieve the default element info.
    $default = [];
    if (($type = $this->getProperty('type')) && $default_info && !$this->hasProperty('defaults_loaded')) {
      // Purposefully use element_info_property() for 7.x and 8.x compatibility.
      $default = element_info_property($type, $property, []);
      $this->setProperty('defaults_loaded', TRUE);
    }

    $existing = &$this->getProperty($property, $default);

    // Add the callback.
    return Plus::addCallback($existing, $callback, $replace, $placement);
  }

  /**
   * Retrieves the class to use for constructing new Element instances.
   *
   * This, essentially, allows themes to sub-class this object.
   *
   * @return string
   *   The class name.
   */
  public static function getElementClass() {
    $class = Plus::getActiveTheme()->getElementClass();
    if ($class !== self::class && !is_subclass_of($class, self::class)) {
      throw new \LogicException('Element class provided by theme must subclass \Drupal\plus\Utility\Element.');
    }
    return $class;
  }

  /**
   * {@inheritdoc}
   */
  public function &__get($key) {
    if (CoreElement::property($key)) {
      throw new \InvalidArgumentException('Cannot dynamically retrieve element property. Please use \Drupal\plus\Utility\Element::getProperty instead.');
    }
    $instance = new self($this->get($key, []));
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function __set($key, $value) {
    if (CoreElement::property($key)) {
      throw new \InvalidArgumentException('Cannot dynamically retrieve element property. Use \Drupal\plus\Utility\Element::setProperty instead.');
    }
    $this->set($key, ($value instanceof Element ? $value->getArray() : $value));
  }

  /**
   * {@inheritdoc}
   */
  public function __isset($name) {
    if (CoreElement::property($name)) {
      throw new \InvalidArgumentException('Cannot dynamically check if an element has a property. Use \Drupal\plus\Utility\Element::unsetProperty instead.');
    }
    return parent::__isset($name);
  }

  /**
   * {@inheritdoc}
   */
  public function __unset($name) {
    if (CoreElement::property($name)) {
      throw new \InvalidArgumentException('Cannot dynamically unset an element property. Use \Drupal\plus\Utility\Element::hasProperty instead.');
    }
    parent::__unset($name);
  }

  /**
   * Appends a property with a value.
   *
   * @param string $name
   *   The name of the property to set.
   * @param mixed $value
   *   The value of the property to set, passed by reference.
   *
   * @return $this
   */
  public function appendProperty($name, &$value) {
    $property = &$this->getProperty($name);
    $element = $value instanceof Element ? $value : Element::reference($value);

    // If property isn't set, just set it.
    if (!isset($property)) {
      $property = $value;
      return $this;
    }

    if (is_array($property)) {
      $property[] = $element->getArray();
    }
    else {
      $property .= (string) $element->renderPlain();
    }

    return $this;
  }

  /**
   * Identifies the children of an element array, optionally sorted by weight.
   *
   * The children of a element array are those key/value pairs whose key does
   * not start with a '#'. See drupal_render() for details.
   *
   * @param bool $sort
   *   Boolean to indicate whether the children should be sorted by weight.
   *
   * @return array
   *   The array keys of the element's children.
   */
  public function childKeys($sort = FALSE) {
    return CoreElement::children($this->__storage, $sort);
  }

  /**
   * Retrieves the children of an element array, optionally sorted by weight.
   *
   * The children of a element array are those key/value pairs whose key does
   * not start with a '#'. See drupal_render() for details.
   *
   * @param bool $sort
   *   Boolean to indicate whether the children should be sorted by weight.
   *
   * @return \Drupal\plus\Utility\Element[]
   *   An array child elements.
   */
  public function children($sort = FALSE) {
    $children = [];
    foreach ($this->childKeys($sort) as $child) {
      $children[$child] = new self($this->__storage[$child]);
    }
    return $children;
  }

  /**
   * Retrieves the render array for the element.
   *
   * @return array
   *   The element render array, passed by reference.
   */
  public function &getArray() {
    return $this->__storage;
  }

  /**
   * Retrieves a context value from the #context element property, if any.
   *
   * @param string $name
   *   The name of the context key to retrieve.
   * @param mixed $default
   *   Optional. The default value to use if the context $name isn't set.
   *
   * @return mixed|null
   *   The context value or the $default value if not set.
   */
  public function &getContext($name, $default = NULL) {
    $context = &$this->getProperty('context', []);
    if (!isset($context[$name])) {
      $context[$name] = $default;
    }
    return $context[$name];
  }

  /**
   * Returns the error message filed against the given form element.
   *
   * Form errors higher up in the form structure override deeper errors as well
   * as errors on the element itself.
   *
   * @return string|null
   *   Either the error message for this element or NULL if there are no errors.
   *
   * @throws \BadMethodCallException
   *   When the element instance was not constructed with a valid form state
   *   object.
   */
  public function getError() {
    if (!$this->formState) {
      throw new \BadMethodCallException('The element instance must be constructed with a valid form state object to use this method.');
    }
    return $this->formState->getError($this->__storage);
  }

  /**
   * Retrieves the render array for the element.
   *
   * @param string $name
   *   The name of the element property to retrieve, not including the # prefix.
   * @param mixed $default
   *   The default to set if property does not exist.
   *
   * @return mixed
   *   The property value, NULL if not set.
   */
  public function &getProperty($name, $default = NULL) {
    return $this->get("#$name", $default);
  }

  /**
   * Returns the visible children of an element.
   *
   * @return array
   *   The array keys of the element's visible children.
   */
  public function getVisibleChildren() {
    return CoreElement::getVisibleChildren($this->__storage);
  }

  /**
   * Indicates whether the element has an error set.
   *
   * @throws \BadMethodCallException
   *   When the element instance was not constructed with a valid form state
   *   object.
   */
  public function hasError() {
    $error = $this->getError();
    return isset($error);
  }

  /**
   * Indicates whether the element has a specific property.
   *
   * @param string $name
   *   The property to check.
   *
   * @return bool
   *   TRUE or FALSE
   */
  public function hasProperty($name) {
    return $this->exists("#$name");
  }

  /**
   * Indicates whether the element is a button.
   *
   * @return bool
   *   TRUE or FALSE.
   */
  public function isButton() {
    $button_types = ['button', 'submit', 'reset', 'image_button'];
    return !empty($this->__storage['#is_button']) || $this->isType($button_types) || $this->hasClass('button');
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return CoreElement::isEmpty($this->__storage);
  }

  /**
   * Indicates whether a property on the element is empty.
   *
   * @param string $name
   *   The property to check.
   *
   * @return bool
   *   Whether the given property on the element is empty.
   */
  public function isPropertyEmpty($name) {
    return $this->hasProperty($name) && empty($this->getProperty($name));
  }

  /**
   * Checks if a value is a render array.
   *
   * @param mixed $value
   *   The value to check.
   *
   * @return bool
   *   TRUE if the given value is a render array, otherwise FALSE.
   */
  public static function isRenderArray($value) {
    return is_array($value) && (isset($value['#type']) ||
      isset($value['#theme']) || isset($value['#theme_wrappers']) ||
      isset($value['#markup']) || isset($value['#attached']) ||
      isset($value['#cache']) || isset($value['#lazy_builder']) ||
      isset($value['#create_placeholder']) || isset($value['#pre_render']) ||
      isset($value['#post_render']) || isset($value['#process']));
  }

  /**
   * Checks if the element is a specific type of element.
   *
   * @param string|array $type
   *   The element type(s) to check.
   *
   * @return bool
   *   TRUE if element is or one of $type.
   */
  public function isType($type) {
    $property = $this->getProperty('type');
    return $property && in_array($property, (is_array($type) ? $type : [$type]));
  }

  /**
   * Determines if an element is visible.
   *
   * @return bool
   *   TRUE if the element is visible, otherwise FALSE.
   */
  public function isVisible() {
    return CoreElement::isVisibleElement($this->__storage);
  }

  /**
   * Maps an element's properties to its attributes array.
   *
   * @param array $map
   *   An associative array whose keys are element property names and whose
   *   values are the HTML attribute names to set on the corresponding
   *   property; e.g., array('#propertyname' => 'attributename'). If both names
   *   are identical except for the leading '#', then an attribute name value is
   *   sufficient and no property name needs to be specified.
   *
   * @return $this
   */
  public function mapProperties(array $map) {
    CoreElement::setAttributes($this->__storage, $map);
    return $this;
  }

  /**
   * Prepends a property with a value.
   *
   * @param string $name
   *   The name of the property to set.
   * @param mixed $value
   *   The value of the property to set.
   *
   * @return $this
   */
  public function prependProperty($name, $value) {
    $property = &$this->getProperty($name);
    $value = $value instanceof Element ? $value->getArray() : $value;

    // If property isn't set, just set it.
    if (!isset($property)) {
      $property = $value;
      return $this;
    }

    if (is_array($property)) {
      array_unshift($property, Element::reference($value)->getArray());
    }
    else {
      $property = (string) $value . (string) $property;
    }

    return $this;
  }

  /**
   * Gets properties of a structured array element (keys beginning with '#').
   *
   * @return array
   *   An array of property keys for the element.
   */
  public function properties() {
    return CoreElement::properties($this->__storage);
  }

  /**
   * Renders the final element HTML.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   *
   * @throws \LogicException
   *   When called outside of a render context (i.e. outside of a renderRoot(),
   *   renderPlain() or executeInRenderContext() call).
   * @throws \Exception
   *   If a #pre_render callback throws an exception, it is caught to mark the
   *   renderer as no longer being in a root render call, if any. Then the
   *   exception is rethrown.
   */
  public function render() {
    return static::getRenderer()->render($this->__storage);
  }

  /**
   * Executes a callable within a render context.
   *
   * Only for very advanced use cases. Prefer using ::renderRoot() and
   * ::renderPlain() instead.
   *
   * @param \Drupal\Core\Render\RenderContext $context
   *   The render context to execute the callable within.
   * @param callable $callable
   *   (optional) The callable to execute. If not set, it will default to
   *   rendering the current element array.
   *
   * @return mixed
   *   The callable's return value.
   *
   * @throws \LogicException
   *   In case bubbling has failed, can only happen in case of broken code.
   *
   * @see \Drupal\Core\Render\RenderContext
   * @see \Drupal\Core\Render\BubbleableMetadata
   * @see \Drupal\Core\Render\Renderer::executeInRenderContext
   */
  public function renderInContext(RenderContext $context, callable $callable = NULL) {
    $renderer = static::getRenderer();
    if (!isset($callable)) {
      $build = &$this->__storage;
      $callable = function () use (&$build, $renderer) {
        return $renderer->render($build);
      };
    }
    return $renderer->executeInRenderContext($context, $callable);
  }

  /**
   * Renders the final element HTML, ignoring any current RenderContext.
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   */
  public function renderPlain() {
    return static::getRenderer()->renderPlain($this->__storage);
  }

  /**
   * Renders the final element HTML.
   *
   * (Cannot be executed within another render context.)
   *
   * @return \Drupal\Component\Render\MarkupInterface
   *   The rendered HTML.
   */
  public function renderRoot() {
    return static::getRenderer()->renderRoot($this->__storage);
  }

  /**
   * Flags an element as having an error.
   *
   * @param string $message
   *   (optional) The error message to present to the user.
   *
   * @return $this
   *
   * @throws \BadMethodCallException
   *   When the element instance was not constructed with a valid form state
   *   object.
   */
  public function setError($message = '') {
    if (!$this->formState) {
      throw new \BadMethodCallException('The element instance must be constructed with a valid form state object to use this method.');
    }
    $this->formState->setError($this->__storage, $message);
    return $this;
  }

  /**
   * Sets the value for a property.
   *
   * @param string $name
   *   The name of the property to set.
   * @param mixed $value
   *   The value of the property to set.
   * @param bool $recurse
   *   Flag indicating wither to set the same property on child elements.
   *
   * @return $this
   */
  public function setProperty($name, $value, $recurse = FALSE) {
    $this->__storage["#$name"] = $value instanceof Element ? $value->getArray() : $value;
    if ($recurse) {
      foreach ($this->children() as $child) {
        $child->setProperty($name, $value, $recurse);
      }
    }
    return $this;
  }

  /**
   * Removes a property from the element.
   *
   * @param string $name
   *   The name of the property to unset.
   *
   * @return $this
   */
  public function unsetProperty($name) {
    unset($this->__storage["#$name"]);
    return $this;
  }

}
