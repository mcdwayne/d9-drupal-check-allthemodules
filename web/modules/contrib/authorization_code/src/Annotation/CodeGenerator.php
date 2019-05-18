<?php

namespace Drupal\authorization_code\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Plugin Namespace: Plugin\CodeGenerator.
 *
 * For a working example, see
 * \Drupal\authorization_code\Plugin\CodeGenerator\SimpleRng.
 *
 * @see \Drupal\authorization_code\CodeGeneratorInterface
 * @see plugin_api
 *
 * @Annotation
 */
class CodeGenerator extends Plugin {

  /**
   * The plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The plugin title.
   *
   * @var string
   */
  public $title;

}
