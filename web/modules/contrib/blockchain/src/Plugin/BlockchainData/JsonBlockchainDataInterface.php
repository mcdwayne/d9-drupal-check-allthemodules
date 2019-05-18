<?php

namespace Drupal\blockchain\Plugin\BlockchainData;

/**
 * Interface JsonConvertableInterface.
 *
 * @package Drupal\blockchain\Utils
 */
interface JsonBlockchainDataInterface {

  /**
   * Static constructor.
   */
  public static function create(array $values = []);

  /**
   * Converts to json string.
   */
  public function toJson();

  /**
   * Converts to array.
   */
  public function toArray();

  /**
   * Constructor form json string.
   */
  public function fromJson($values);

  /**
   * Constructor form array.
   */
  public function fromArray(array $values);

  /**
   * Getter for widget render array.
   */
  public function getWidget();

  /**
   * Getter for formatter render array.
   */
  public function getFormatter();

  /**
   * Humanises string.
   */
  public function humanize($string);

}
