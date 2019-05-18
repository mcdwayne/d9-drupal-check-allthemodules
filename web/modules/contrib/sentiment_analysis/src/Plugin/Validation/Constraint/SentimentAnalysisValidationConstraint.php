<?php

namespace Drupal\sentiment_analysis\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Url;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Checks that the node.
 *
 * @Constraint(
 *   id = "SentimentAnalysisValidationConstraint",
 *   label = @Translation("Sentiment Analysis Field Validation Constraint"),
 * )
 */
class SentimentAnalysisValidationConstraint extends Constraint {
   
}