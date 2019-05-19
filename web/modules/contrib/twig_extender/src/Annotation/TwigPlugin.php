<?php

namespace Drupal\twig_extender\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a TwigPlugin annotation object.
 *
 * Plugin namespace: Plugin\TwigPlugin.
 *
 * @Annotation
 */
class TwigPlugin extends Plugin {
  /**
   * The plugin ID.
   *
   * @var string
   */
  public $id;

  /**
   * The twig plugin type.
   *
   * Available options:
   *  - filter: A twig filter plugin
   *  - function: A twig function.
   *
   * @var string
   */
  public $type = 'filter';

  /**
   * The twig filter or function name available in twig templates.
   *
   * @var string
   */
  public $name = 'name';

  /**
   * The twig plugin function for process the output.
   *
   * @var string
   */
  public $function = 'function';

}
