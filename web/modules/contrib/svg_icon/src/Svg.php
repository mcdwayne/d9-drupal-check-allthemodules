<?php

namespace Drupal\svg_icon;

/**
 * @method int getId();
 * @method int getHeight();
 * @method int getWidth();
 * @method string getViewBox();
 * @method string getFill();
 */
class Svg {

  protected $attributes;
  protected $title;
  protected $children;
  protected $isSprite;

  public function __construct($domElement) {
    $domElement = $this->loadXml($domElement);

    // Special handling for "sprites"
    if (!empty($domElement->symbol)) {
      $symbols = $domElement;
      $this->isSprite = TRUE;
    }
    elseif (!empty($domElement->defs->symbol)) {
      $symbols = $domElement->defs->symbol;
      $this->isSprite = TRUE;
    }
    else {
      $symbols = $domElement;
    }

    // Recursively build out the child elements.
    foreach ($symbols as $fragment) {
      $this->children[] = new Svg($fragment);
    }

    // Store the attributes at each level.
    $attributes = $domElement->attributes();
    foreach ($attributes as $key => $value) {
      $this->attributes[$key] = (string) $value;
    }

    // Keep the XML around as well.
    $this->xmlString = $domElement->asXML();

    // Handle the title element.
    $this->title = !empty($domElement->title) ? (string) $domElement->title : '';
  }

  public function getTitle() {
    return trim($this->title);
  }

  public function getFillRule() {
    return $this->attributes['evenodd'];
  }

  public function isSprite() {
    return $this->isSprite;
  }

  /**
   * Dynamically implement our attribute getters.
   *
   * @param string $name
   *   The method name.
   * @param array $arguments
   *   An array of arguments.
   *
   * @return string
   *   The method result.
   */
  public function __call($name, $arguments) {
    if (substr($name, 0, 3) === 'get') {
      $key = lcfirst(substr($name, 3));
      return $this->getAttribute($key);
    }

    throw new \BadMethodCallException();
  }

  protected function getAttribute($key) {
    return !empty($this->attributes[$key]) ? $this->attributes[$key] : '';
  }

  /**
   * @return \Drupal\svg_icon\Svg[]
   */
  public function getChildren() {
    return $this->children;
  }

  public function getXml() {
    return $this->xmlString;
  }

  protected function loadXml($domElement) {
    if (is_string($domElement)) {
      $domElement = simplexml_load_string($domElement);
    }
    if (!$domElement instanceof \SimpleXMLElement) {
      throw new \InvalidArgumentException('The string or element is not a valid SVG file');
    }
    return $domElement;
  }

}

