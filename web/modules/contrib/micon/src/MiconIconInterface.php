<?php

namespace Drupal\micon;

/**
 * Defines an object which can be rendered by the Render API.
 */
interface MiconIconInterface {

  /**
   * Get the type of the IcoMoon package.
   *
   * @return string
   *   The type of package. Either image or font.
   */
  public function getType();

  /**
   * Get the id of the Micon package.
   *
   * @return string
   *   The package entity id.
   */
  public function getPackageId();

  /**
   * Get the label of the Micon package.
   *
   * @return string
   *   The package entity label.
   */
  public function getPackageLabel();

  /**
   * Get the unique prefix of the IcoMoon package.
   *
   * @return string
   *   The IcoMoon prefix.
   */
  public function getPrefix();

  /**
   * Get the unique tag of the IcoMoon icon.
   *
   * @return string
   *   The IcoMoon icon.
   */
  public function getTag();

  /**
   * Get the unique selector of the IcoMoon icon.
   *
   * @return string
   *   The CSS selector.
   */
  public function getSelector();

  /**
   * Get the HEX used within sudo-elements of CSS.
   *
   * @return string
   *   The CSS HEX value.
   */
  public function getHex();

  /**
   * Get the wrapping element HTML element type.
   *
   * @return string
   *   The wrapping tag used within the template.
   */
  public function getWrappingElement();

  /**
   * Get the content entered within the icon tags.
   *
   * @return mixed[]
   *   A render array.
   */
  public function getChildren();

  /**
   * Sets attributes.
   *
   * @param array $attributes
   *   An attributes array.
   *
   * @return $this
   */
  public function setAttributes(array $attributes);

  /**
   * Sets values for an attribute key.
   *
   * @param string $attribute
   *   Name of the attribute.
   * @param string|array $value
   *   Value(s) to set for the given attribute key.
   *
   * @return $this
   */
  public function setAttribute($attribute, $value);

  /**
   * Adds classes or merges them on to array of existing CSS classes.
   *
   * @param string|array ...
   *   CSS classes to add to the class attribute array.
   *
   * @return $this
   */
  public function addClass($classes);

  /**
   * Returns a render array representation of the object.
   *
   * @return mixed[]
   *   A render array.
   */
  public function toRenderable();

  /**
   * Returns a fully rendered Markup representation of the object.
   *
   * @return \Drupal\Core\Render\Markup
   *   A Markup object.
   */
  public function toMarkup();

  /**
   * Returns a trimmed, json encoded string of the rendered markup.
   *
   * @return string
   *   A json encoded string.
   */
  public function toJson();

}
