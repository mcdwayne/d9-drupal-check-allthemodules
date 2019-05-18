<?php

namespace Drupal\onlyone\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that Only One node for each language exists.
 *
 * @Constraint(
 *   id = "OnlyOne",
 *   label = @Translation("OnlyOne Constraint", context = "Validation"),
 *   type = "entity:node"
 * )
 */
class OnlyOneConstraint extends Constraint {

  /**
   * Message shown when a node of a language exists on a content type.
   *
   * The message that will be shown when a node of a language exists on
   * a configured content type and you try to add another node for the same
   * language.
   *
   * @var string
   *
   * @see https://www.drupal.org/project/onlyone/issues/2962186
   * @see https://www.drupal.org/project/onlyone/issues/2969293
   */
  public $nodeExists = "The content type %content_type is configured to have Only One node per language but the node <a href=':href'>@title</a> exists for the %language language.";

}
