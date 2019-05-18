<?php

namespace Drupal\reference_map\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the map is valid.
 *
 * @Constraint(
 *   id = "ReferenceMapMap",
 *   label = @Translation("Reference Map Map", context = "Validation"),
 *   type = "entity:reference_map_config"
 * )
 */
class ReferenceMapMap extends Constraint {

  /**
   * The message to show if the map is not an array.
   *
   * @var string
   */
  public $mapNotArray = "The map is not an array.";

  /**
   * The message to show if source and destination steps don't exist.
   *
   * @var string
   */
  public $mapMinimumSteps = 'The map needs to have at a minimum two steps, the first containing the source and the last containing the destination.';

  /**
   * The message to show if the step isn't an array.
   *
   * @var string
   */
  public $stepNotArray = 'Step %position is not an array.';

  /**
   * The message to show if the step is missing a required key.
   *
   * @var string
   */
  public $stepMissingKey = 'Step %position is missing the required %key key.';

  /**
   * The message to show if the entity type is invalid.
   *
   * @var string
   */
  public $stepInvalidEntityType = 'The entity type at step %position is invalid.';

  /**
   * The message to show if the bundles key is not an array.
   *
   * @var string
   */
  public $stepBundlesNotArray = 'The bundles key for step %position is not an array.';

  /**
   * The message to show if the bundles key has an invalid bundle.
   *
   * @var string
   */
  public $stepInvalidBundles = 'The bundle %bundles for step %position is invalid.|The bundles %bundles for step %position are invalid.';

  /**
   * The message to show if the last step has a field_name key.
   *
   * @var string
   */
  public $stepLastHasFieldName = 'The last step must not have a field_name key.';

  /**
   * The message to show if the field_name key is invalid for the entity type.
   *
   * @var string
   */
  public $stepInvalidFieldNameEntityType = "The entity type for step %position doesn't have a %field field.";

  /**
   * The message to show if the field_name key is invalid for the bundle.
   *
   * @var string
   */
  public $stepInvalidFieldNameBundle = "The %bundle bundle for step %position doesn't have a %field field.";

}
