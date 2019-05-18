<?php

namespace Drupal\formassembly\Component\Render;

use Drupal\Component\Render\MarkupInterface;

/**
 * Returns an unmodified string.
 *
 * Should only be used in passing through FormAssembly markup.
 *
 * @author Shawn P. Duncan <code@sd.shawnduncan.org>
 *
 * Copyright 2018 by Shawn P. Duncan.  This code is
 * released under the GNU General Public License.
 * Which means that it is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or (at
 * your option) any later version.
 * http://www.gnu.org/licenses/gpl.html
 */
class FormBodyUnescapedMarkup implements MarkupInterface {

  /**
   * The string to return.
   *
   * @var string
   */
  protected $string;

  /**
   * Constructs an FormBodyUnescapedMarkup object.
   *
   * @param string $string
   *   The string to escape. This value will be cast to a string.
   */
  public function __construct($string) {
    $this->string = (string) $string;
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return $this->string;
  }

  /**
   * {@inheritdoc}
   */
  public function jsonSerialize() {
    return $this->__toString();
  }

}
