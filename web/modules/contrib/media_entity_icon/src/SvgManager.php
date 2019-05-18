<?php

namespace Drupal\media_entity_icon;

/**
 * Helper methods for handling SVG files.
 *
 * @package Drupal\media_entity_icon
 */
class SvgManager implements SvgManagerInterface {

  /**
   * Static cache of SimpleXMLElement instances.
   *
   * @var array
   */
  protected $simpleXmlElements = [];

  /**
   * Static cache of extracted icon ids.
   *
   * @var array
   */
  protected $cachedExtractedIds = [];

  /**
   * SvgManager constructor.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public function getIconSize($svg_path, $icon_id) {
    $size = [];
    $svg_source = $this->getSimpleXml($svg_path);

    // Find matching element.
    $elements = $svg_source ? $svg_source->xpath('//ns:symbol[@id="' . $icon_id . '"]') : NULL;
    if (!isset($elements[0])) {
      return $size;
    }

    // Gather meaningful attributes.
    $attributes = $elements[0]->attributes();
    if (isset($attributes['viewBox'])) {
      list(,, $width, $height) = explode(' ', $attributes['viewBox']);
      $size['width'] = $width;
      $size['height'] = $height;
    }
    else {
      if (isset($attributes['width'])) {
        $size['width'] = $attributes['width'];
      }
      if (isset($attributes['height'])) {
        $size['height'] = $attributes['height'];
      }
    }

    return $size;
  }

  /**
   * {@inheritdoc}
   */
  public function extractIconIds($svg_path) {
    if (isset($this->cachedExtractedIds[$svg_path])) {
      return $this->cachedExtractedIds[$svg_path];
    }

    $ids = [];
    $svg_source = $this->getSimpleXml($svg_path);

    // Find matching element.
    $elements = $svg_source ? $svg_source->xpath('//ns:symbol[@id]') : NULL;
    if (empty($elements)) {
      return $ids;
    }

    foreach ($elements as $element) {
      $attributes = $element->attributes();
      $id = !empty($attributes->id) ? $attributes->id->__toString() : NULL;
      if (isset($id)) {
        $ids[$id] = $id;
      }
    }

    $this->cachedExtractedIds[$svg_path] = $ids;

    return $this->cachedExtractedIds[$svg_path];
  }

  /**
   * {@inheritdoc}
   */
  public function extractIconAsSvg($svg_path, $icon_id) {
    $svg_source = $this->getSimpleXml($svg_path);

    // Find matching element.
    $elements = $svg_source ? $svg_source->xpath('//ns:symbol[@id="' . $icon_id . '"]') : NULL;
    if (!isset($elements[0])) {
      return NULL;
    }

    // New SVG.
    $svg = new \SimpleXMLElement('<svg></svg>');
    $svg->addAttribute('xmlns', 'http://www.w3.org/2000/svg');
    foreach ($elements[0]->attributes() as $attr => $value) {
      $svg->addAttribute($attr, $value);
    }
    /** @var \SimpleXMLElement $data */
    foreach ($elements[0]->children() as $name => $data) {
      $xmlElement = $svg->addChild($name, $data);
      foreach ($data->attributes() as $attr => $value) {
        $xmlElement->addAttribute($attr, $value);
      }
    }

    // Return SVG.
    return $svg->asXML();
  }

  /**
   * Get SimpleXML from SVG file, store it in static cache to avoid redundancy.
   *
   * @param string $svg_path
   *   SVG local or distant path.
   *
   * @return \SimpleXMLElement|string
   *   XML element.
   */
  protected function getSimpleXml($svg_path) {
    // Check static cache.
    if (isset($this->simpleXmlElements[$svg_path])) {
      return $this->simpleXmlElements[$svg_path];
    }

    $simple_xml = simplexml_load_file($svg_path);
    if (!$simple_xml) {
      $this->simpleXmlElements[$svg_path] = FALSE;
    }
    else {
      $simple_xml->registerXPathNamespace('ns', 'http://www.w3.org/2000/svg');
      $this->simpleXmlElements[$svg_path] = $simple_xml;
    }

    return $this->simpleXmlElements[$svg_path];
  }

}
