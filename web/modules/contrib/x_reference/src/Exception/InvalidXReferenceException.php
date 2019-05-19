<?php


namespace Drupal\x_reference\Exception;

use Drupal\Core\Entity\EntityConstraintViolationListInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * Class InvalidXReferenceException
 *
 * @package Drupal\x_reference\Exception
 */
class InvalidXReferenceException extends \RuntimeException {

  /**
   * Construct an InvalidXReferenceException exception.
   *
   * For the remaining parameters see \Exception.
   *
   * @param EntityConstraintViolationListInterface $violations
   * @param string $message
   * @param int $code
   * @param \Exception|null $previous
   *
   * @see \Exception
   */
  public function __construct($violations, $message = '', $code = 0, \Exception $previous = NULL) {
    if (empty($message)) {
      $message = "Unprocessable Entity: validation failed.\n";
      /** @var ConstraintViolationInterface $violation */
      foreach ($violations as $violation) {
        $message .= $violation->getPropertyPath() . ': ' . $violation->getMessage() . "\n";
      }
    }
    parent::__construct($message, $code, $previous);
  }

}
