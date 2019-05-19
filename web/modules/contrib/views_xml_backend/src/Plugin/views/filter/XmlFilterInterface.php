<?php

/**
 * @file
 * Contains \Drupal\views_xml_backend\Plugin\views\filter\XmlFilterInterface.
 */

namespace Drupal\views_xml_backend\Plugin\views\filter;

/**
 * Filter plugins that are compatible with views_xml_backend.
 */
interface XmlFilterInterface {

  /**
   * Generates an XPath filter string.
   *
   * @return string
   *   The XPath filter string.
   */
  public function __toString();

}
