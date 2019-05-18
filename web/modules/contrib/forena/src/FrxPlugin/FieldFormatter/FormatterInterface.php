<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 3/28/16
 * Time: 6:28 AM
 */

namespace Drupal\forena\FrxPlugin\FieldFormatter;

/**
 * Defines contract for a field formatter.
 */
interface FormatterInterface {

  /**
   * @return array
   *   List of formatting methods in object
   */
  public function formats();

}