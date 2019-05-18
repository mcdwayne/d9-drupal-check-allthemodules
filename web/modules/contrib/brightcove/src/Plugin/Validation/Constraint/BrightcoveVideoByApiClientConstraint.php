<?php

namespace Drupal\brightcove\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Defines constraint for BrightcoveVideo referenced entity by API client.
 *
 * @Constraint(
 *   id = "brightcove_video_by_api_client_constraint",
 *   label = @Translation("BrightcoveVideo constraint", context = "Validation"),
 * );
 */
class BrightcoveVideoByApiClientConstraint extends Constraint {

  /**
   * Error message for missing API client ID.
   *
   * @var string
   */
  public $missingApiClient = "API client ID is missing.";

}
