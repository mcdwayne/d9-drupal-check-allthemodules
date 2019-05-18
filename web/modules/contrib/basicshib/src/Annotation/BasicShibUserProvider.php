<?php
/**
 * Created by PhpStorm.
 * User: th140
 * Date: 11/17/17
 * Time: 8:26 AM
 */

namespace Drupal\basicshib\Annotation;


use Drupal\Component\Annotation\Plugin;

/**
 * Class BasicShibUserProvider
 *
 * @package Drupal\basicshib\Annotation
 *
 * @Annotation
 */
class BasicShibUserProvider extends Plugin {
  /**
   * @var string
   */
  public $id;

  /**
   * @var string
   */
  public $name;
}
