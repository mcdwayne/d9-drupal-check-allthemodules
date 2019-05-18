<?php
/**
 * Created by PhpStorm.
 * User: twhiston
 * Date: 05.02.17
 * Time: 16:13
 */

namespace Drupal\elastic_search\Plugin\FieldMapper\FormHelper;

/**
 * Class AbstractTranslation
 *
 * @package Drupal\elastic_search\Plugin\FieldMapper\FormHelper
 *
 * @codeCoverageIgnore
 */
trait AbstractTranslation {

  /**
   * @param string $string
   * @param array  $args
   * @param array  $options
   *
   * @return mixed
   */
  abstract protected function t($string, array $args = [], array $options = []);

}