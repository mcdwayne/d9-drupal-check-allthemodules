<?php
/**
 * Created by PhpStorm.
 * User: metzlerd
 * Date: 1/31/16
 * Time: 4:21 PM
 */

namespace Drupal\forena\FrxPlugin\Driver;


interface DriverInterface {

  public function access($right);

  /**
   * Retreives data based on parsed definition.
   *
   * @param $block
   *   Block definition array
   * @return mixed
   *   Iterateable data structure, ideally \SimpleXMLElement
   */
  public function data(Array $block, $raw_mode = FALSE);

  /**
   * @param string $block_name
   *   Name of the block to load
   * @return mixed
   */
  public function loadBlock($block_name);
}