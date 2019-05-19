<?php

namespace Drupal\views_oai_pmh\Service;

use Picturae\OaiPmh\Provider as BaseProvider;
use Drupal\Component\Render\MarkupInterface;

/**
 *
 */
class Provider extends BaseProvider implements MarkupInterface {

  /**
   *
   */
  public function jsonSerialize() {
    return '';
  }

  /**
   *
   */
  public function __toString() {
    return $this->getResponse()->getBody()->getContents();
  }

}
