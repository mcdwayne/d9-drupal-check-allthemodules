<?php

namespace Drupal\elastic_search\Exception;

use Drupal\elastic_search\ValueObject\IdDetails;

/**
 * Class CartographerMappingException
 *
 * @package Drupal\elastic_search\Exception
 * @codeCoverageIgnore
 */
class CartographerMappingException extends \Exception {

  /**
   * @var \Drupal\elastic_search\ValueObject\IdDetails
   */
  private $id;

  /**
   * @param \Drupal\elastic_search\ValueObject\IdDetails $id
   */
  public function setId(IdDetails $id) {
    $this->id = $id;
  }

  /**
   * @return \Drupal\elastic_search\ValueObject\IdDetails
   */
  public function getId(): IdDetails {
    return $this->id;
  }

}