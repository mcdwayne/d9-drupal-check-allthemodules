<?php

namespace Drupal\multiversion\Entity\Exception;

use Drupal\Core\Entity\EntityInterface;

class MultiversionException extends \Exception implements MultiversionExceptionInterface {

  /**
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * @param EntityInterface $entity
   * @param null|string $message
   * @param int $code
   * @param \Exception $previous
   */
  public function __construct(EntityInterface $entity = NULL, $message = NULL, $code = 0, \Exception $previous = NULL) {
    $this->entity = $entity;

    parent::__construct($message, $code, $previous);
  }

  public function getEntity() {
    return $this->entity;
  }

}
