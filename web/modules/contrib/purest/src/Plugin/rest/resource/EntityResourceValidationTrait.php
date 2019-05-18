<?php

namespace Drupal\purest\Plugin\rest\resource;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

/**
 * EntityResourceValidationTrait.
 *
 * @internal
 */
trait EntityResourceValidationTrait {

  /**
   * Verifies that the whole entity does not violate any validation constraints.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to validate.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException
   *   If validation errors are found.
   */
  protected function validate(EntityInterface $entity, array $fields_to_validate = []) {
    // @todo Remove when https://www.drupal.org/node/2164373 is committed.
    if (!$entity instanceof FieldableEntityInterface) {
      return;
    }

    $violations = $entity->validate();

    // Remove violations of inaccessible fields as they cannot stem from our
    // changes.
    $violations->filterByFieldAccess();

    if ($violations->count() > 0) {
      $message = "Unprocessable Entity: validation failed.\n";

      foreach ($violations as $violation) {
        // We strip all HTML from the error message to have a nicer rest
        // response message.
        $message .= $violation->getPropertyPath() . ': ' . PlainTextOutput::renderFromHtml($violation->getMessage()) . "\n";
      }

      throw new UnprocessableEntityHttpException($message);
    }
  }

}
