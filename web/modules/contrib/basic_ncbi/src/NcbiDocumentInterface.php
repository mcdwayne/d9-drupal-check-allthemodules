<?php

namespace Drupal\basic_ncbi;

use SimpleXMLElement;

/**
 * Interface NcbiDocumentInterface.
 */
interface NcbiDocumentInterface {

  /**
   * Construct from SimpleXMLElement.
   *
   * @param \SimpleXMLElement $xml
   *   Xml containing data.
   */
  public function __construct(SimpleXMLElement $xml);

  /**
   * Transform Object in Array.
   *
   * @return array
   *   The object as an array.
   */
  public function toArray();

}
