<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/17/17
 * Time: 8:58 AM
 */

namespace Drupal\basicshib\Annotation;


use Drupal\Component\Annotation\Plugin;

/**
 * Class AuthenticationFilter
 *
 * @package Drupal\basicshib\Annotation
 *
 * @Annotation
 */
class BasicShibAuthFilter extends Plugin {
  /**
   * @var string
   */
  public $id;

  /**
   * @var string
   */
  public $name;
}
