<?php

namespace Drupal\inmail\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines the plugin annotation of message handlers.
 *
 * @ingroup handler
 *
 * @Annotation
 */
class Handler extends Plugin {

  /**
   * The short machine-name to uniquely identify the handler.
   *
   * @var string
   */
  protected $id;

  /**
   * The display label of the handler.
   *
   * @var \Drupal\Core\StringTranslation\TranslationWrapper
   */
  protected $label;

  /**
   * The brief description of the purpose or functionality of the handler.
   *
   * A more detailed description should be given by implementing help().
   *
   * @see \Drupal\inmail\Plugin\inmail\Handler\HandlerInterface::help().
   *
   * @var \Drupal\Core\StringTranslation\TranslationWrapper
   */
  protected $description;

}
