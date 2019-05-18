<?php

/**
 * @file
 * Contains \Drupal\collect\Annotation\Processor.
 */

namespace Drupal\collect\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Plugin annotation for Collect post-processors.
 *
 * @Annotation
 *
 * @todo Include context.
 */
class Processor extends Plugin {

  /**
   * The processor plugin ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The processor plugin label.
   *
   * @var \Drupal\Core\StringTranslation\TranslationWrapper
   */
  protected $label;

  /**
   * The processor plugin description.
   *
   * @var \Drupal\Core\StringTranslation\TranslationWrapper
   */
  protected $description;

}
