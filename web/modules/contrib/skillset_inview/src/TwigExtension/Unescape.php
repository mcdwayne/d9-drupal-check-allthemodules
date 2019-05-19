<?php

namespace Drupal\skillset_inview\TwigExtension;

/**
 * A Twig extension (filter) converts escaped html to unescaped.
 *
 * As, in general Drupal no longer supports the '!' placeholder twig
 * escapes all output I need to re-normallize output. This is a
 * work-around issue.
 */
class Unescape extends \Twig_Extension {

  /**
   * An empty Constructor.  Parview warning.
   */
  public function __construct() {}

  /**
   * Generates a list of all Twig filters that this extension defines.
   */
  public function getFilters() {
    return [
      new \Twig_SimpleFilter('unescape', [$this, 'unescape'], ['is_safe' => ['html']]),
    ];
  }

  /**
   * Gets a unique identifier for this Twig extension.
   */
  public function getName() {
    return 'skillset_inview.twig.unescape';
  }

  /**
   * Bypass twig t escaping issue.
   */
  public function unescape($value) {
    return html_entity_decode($value, ENT_QUOTES, 'UTF-8');
  }

}
