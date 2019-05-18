<?php

namespace Drupal\aws_cloud\Plugin\Validation\Constraint;

use Drupal\Core\Entity\Plugin\Validation\Constraint\CompositeConstraintBase;

/**
 * AWS specific validation for user specified fields.
 *
 * @Constraint(
 *   id = "AWSConstraint",
 *   label = @Translation("Instance Type", context = "Validation"),
 *   type = "entity:cloud_server_template"
 * )
 */
class AWSConstraint extends CompositeConstraintBase {

  /**
   * Error message if a network is not selected.
   *
   * @var string
   */
  public $noNetwork = 'The %instance_type requires a network selection';

  /**
   * Error message if shutdown behavior = stop.
   *
   * @var string
   */
  public $shutdownBehavior = 'Only EBS backed images can use Stop as the Instance Shutdown Behavior';

  /**
   * {@inheritdoc}
   */
  public function coversFields() {
    return [
      'field_instance_type',
      'field_network',
      'field_image_id',
      'field_instance_shutdown_behavior',
    ];
  }

}
