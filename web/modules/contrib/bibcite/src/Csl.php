<?php

namespace Drupal\bibcite;

/**
 * Simple wrapper for CSL XML.
 */
class Csl {

  /**
   * CSL content.
   *
   * @var \SimpleXMLElement
   */
  protected $xml;

  /**
   * Csl constructor.
   *
   * @param string $csl_content
   *   CSL content.
   */
  public function __construct($csl_content) {
    $this->xml = simplexml_load_string($csl_content);
  }

  /**
   * Return XML string.
   *
   * @return string
   *   XML content as string.
   */
  public function __toString() {
    return (string) $this->xml->asXML();
  }

  /**
   * Get CSL style identifier.
   *
   * @return string
   *   Identifier of the style
   */
  public function getId() {
    return (string) $this->xml->info->id;
  }

  /**
   * Get CSL style title.
   *
   * @return string
   *   Title of the style.
   */
  public function getTitle() {
    return (string) $this->xml->info->title;
  }

  /**
   * Get parent style identifier.
   *
   * @return null|string
   *   Identifier of the parent style or NULL.
   */
  public function getParent() {
    /** @var \SimpleXMLElement $link */
    foreach ($this->xml->info->link as $link) {
      $attributes = $link->attributes();
      if (isset($attributes->rel, $attributes->href) && (string) $attributes->rel === 'independent-parent') {
        return (string) $attributes->href;
      }
    }

    return NULL;
  }

  /**
   * Validate CSL style.
   *
   * @todo This is a very simple XML validation. Need to be replaced by some CSL validation mechanism.
   *
   * @return bool
   *   TRUE if provided CSL is valid, FALSE if not.
   */
  public function validate() {
    return (bool) $this->xml;
  }

}
