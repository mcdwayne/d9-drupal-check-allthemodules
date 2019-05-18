<?php

/**
 * @file
 * Contains Drupal\inline\Parser\ParserInterface.
 */

namespace Drupal\inline\Parser;

use Drupal\inline\MacroInterface;

/**
 * Defines the interface for all inline macro implementations.
 */
interface ParserInterface {

  public function parse($text, array $implementations);

  public function serialize(MacroInterface $macro);

}
