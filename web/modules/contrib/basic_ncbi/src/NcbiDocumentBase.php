<?php

namespace Drupal\basic_ncbi;

/**
 * Class NcbiDocumentBase.
 */
abstract class NcbiDocumentBase implements NcbiDocumentInterface {

  /**
   * Universal getter.
   *
   * @param string $key
   *   Attribute needed.
   *
   * @return mixed
   *   Attribute value.
   */
  public function get($key) {
    if (isset($this->$key)) {
      return $this->$key;
    }
    return NULL;
  }

}
