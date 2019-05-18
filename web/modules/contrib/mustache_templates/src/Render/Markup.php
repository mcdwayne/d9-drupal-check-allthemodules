<?php

namespace Drupal\mustache\Render;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Component\Render\MarkupTrait;

/**
 * Defines an object that passes safe strings through the render system.
 *
 * This object should only be constructed with a known safe string. If there is
 * any risk that the string contains user-entered data that has not been
 * filtered first, it must not be used.
 *
 * @internal
 *   This class must only be used inside the mustache_templates project.
 */
final class Markup implements MarkupInterface, \Countable {

  use MarkupTrait;

}
