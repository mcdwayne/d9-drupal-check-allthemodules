<?php

/**
 * @file
 * Contains \Drupal\collect\Annotation\Model.
 */

namespace Drupal\collect\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Annotation for Collect model plugins.
 *
 * @Annotation
 */
class Model extends Plugin {

  /**
   * The model plugin ID.
   *
   * @var string
   */
  protected $id;

  /**
   * The model plugin label.
   *
   * @var \Drupal\Core\StringTranslation\TranslationWrapper
   */
  protected $label;

  /**
   * The model plugin description.
   *
   * @var \Drupal\Core\StringTranslation\TranslationWrapper
   */
  protected $description;

  /**
   * Schema URIs that this model plugin can be applied to.
   *
   * @var array
   */
  protected $patterns;

  /**
   * Whether the model plugin should be excluded from config UI plugin selector.
   *
   * @var bool
   */
  protected $hidden;

}
