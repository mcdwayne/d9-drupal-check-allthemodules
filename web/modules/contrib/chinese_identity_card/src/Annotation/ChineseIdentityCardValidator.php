<?php

namespace Drupal\chinese_identity_card\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Chinese identity card validator item annotation object.
 *
 * @see \Drupal\chinese_identity_card\Plugin\ChineseIdentityCardValidatorManager
 * @see plugin_api
 *
 * @Annotation
 */
class ChineseIdentityCardValidator extends Plugin {


  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The label of the plugin.
   *
   * @var \Drupal\Core\Annotation\Translation
   *
   * @ingroup plugin_translatable
   */
  public $label;

}
