<?php

namespace Drupal\brightcove\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates BrightcoveVideo reference by API client.
 */
class BrightcoveVideoByApiClientConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /* @var \Drupal\Core\Field\EntityReferenceFieldItemList $videos */
    /* @var \Drupal\brightcove\Plugin\Validation\Constraint\BrightcoveVideoByApiClientConstraint $constraint */

    // Get API client.
    $api_client = $value->get('api_client')->getValue();
    if (!empty($api_client[0]['target_id'])) {
      // Get the videos field settings.
      $videos = $value->get('videos');
      /* @var \Drupal\Core\Field\TypedData\FieldItemDataDefinition $item_definitions */
      $item_definitions = $videos->getItemDefinition();
      $settings = $item_definitions->getSettings();

      // Set the required view argument for the videos field in order to be able
      // to validate the field properly.
      $settings['handler_settings']['view']['arguments'] = [$api_client[0]['target_id']];
      $item_definitions->setSettings($settings);
    }
    else {
      // Set violation if the API client is missing.
      $this->context->addViolation($constraint->missingApiClient);
    }
  }

}
