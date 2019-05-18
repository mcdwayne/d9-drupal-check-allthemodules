<?php

namespace Drupal\inmail\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the plugin annotation of a mail deliverer.
 *
 * @ingroup deliverer
 *
 * @Annotation
 */
class Deliverer extends Plugin {

  /**
   * The short machine-name to uniquely identify the deliverer.
   *
   * @var string
   */
  protected $id;

  /**
   * The display label of the deliverer.
   *
   * @var \Drupal\Core\StringTranslation\TranslationWrapper
   */
  protected $label;

}
