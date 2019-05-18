<?php

namespace Drupal\entity_counter_webform\Plugin\EntityCounterCondition;

use Drupal\entity_counter\Plugin\EntityCounterConditionEntityBundleBase;

/**
 * Provides the bundle condition for webform submissions.
 *
 * @EntityCounterCondition(
 *   id = "webform_submission_bundle",
 *   label = @Translation("Webform submission bundle"),
 *   category = @Translation("Webform submission"),
 *   entity_type = "webform_submission",
 * )
 */
class WebformSubmissionBundle extends EntityCounterConditionEntityBundleBase {

}
