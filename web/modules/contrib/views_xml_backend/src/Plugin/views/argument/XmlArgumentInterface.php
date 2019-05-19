<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\argument\XmlArgumentInterface.
 */

namespace Drupal\views_xml_backend\Plugin\views\argument;

/**
 * Argument plugins that are compatible with views_xml_backend.
 */
interface XmlArgumentInterface {

  /**
   * Generates an XPath argument string.
   *
   * @return string
   *   The XPath argument string.
   */
  public function __toString();

}
