<?php

namespace Drupal\preprocess\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Preprocess annotation object.
 *
 * Plugin namespace: Plugin\Preprocess.
 *
 * @see \Drupal\preprocess\PreprocessInterface
 * @see \Drupal\preprocess\PreprocessPluginManager
 * @see plugin_api
 *
 * @Annotation
 */
class Preprocess extends Plugin {

  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The preprocess hook that this plugin implements.
   *
   * Corresponds to hook_preprocess_HOOK();
   *
   * @var string
   */
  public $hook;

  /**
   * The preprocess plugin class.
   *
   * This default value is used for plugins defined in preprocessors.yml that
   * do not specify a class themselves.
   *
   * @var string
   */
  public $class = '';

}
