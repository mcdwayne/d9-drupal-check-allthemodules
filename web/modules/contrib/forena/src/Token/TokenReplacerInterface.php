<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/18/15
 * Time: 10:43 AM
 */

namespace Drupal\forena\Token;


interface TokenReplacerInterface {
  /**
   * @param $text
   * @param bool $raw Raw=true skips the translation/formatting steps.
   * @return mixed
   * The replacer method replaces text.
   */
  public function replace($text, $raw=FALSE);

  /**
   * @param $text
   * @return mixed
   * Return the tokens contained in the text.
   */
  public function tokens($text);

  /**
   * @param $condition
   * @return mixed
   * Provides test replacement that lets us test whether an expression is true or false.
   */
  public function test($condition);
}