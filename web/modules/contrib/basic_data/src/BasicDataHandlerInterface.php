<?php

namespace Drupal\basic_data;

use Drupal\basic_data\Entity\BasicDataInterface;

/**
 * Interface BasicDataHandlerInterface.
 */
interface BasicDataHandlerInterface {

  /**
   * @param $type
   * @param $body
   * @param null $values
   *
   * @return bool|mixed
   */
  public function createBasicData($type, $body, $values = NULL);

  /**
   * @param \Drupal\basic_data\Entity\BasicDataInterface $data
   *
   * @return \Drupal\basic_data\Entity\BasicDataInterface
   */
  public function saveData(BasicDataInterface $data);

  /**
   * @param $message
   */
  public function logError($message);

  /**
   * Load the basic_data storage so we can save stuff.
   *
   * @return bool|\Drupal\Core\Entity\EntityStorageInterface
   */
  public function getStorage();

}
