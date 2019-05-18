<?php

namespace Drupal\password_strength\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Password Strength matcher annotation object.
 *
 * @Annotation
 */
class PasswordStrengthMatcher extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the matcher.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $title;

  /**
   * The description shown to users.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;


}
