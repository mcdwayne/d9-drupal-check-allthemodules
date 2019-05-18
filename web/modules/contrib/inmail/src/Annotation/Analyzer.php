<?php

namespace Drupal\inmail\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the plugin annotation of message analyzers.
 *
 * @Annotation
 */
class Analyzer extends Plugin {

  /**
   * The short machine-name to uniquely identify the analyzer.
   *
   * @var string
   */
  protected $id;

  /**
   * The display label of the analyzer.
   *
   * @var \Drupal\Core\StringTranslation\TranslationWrapper
   */
  protected $label;

}
