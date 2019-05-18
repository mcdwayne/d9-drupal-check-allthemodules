<?php

namespace Drupal\rules_webform\Plugin\Condition;

use Drupal\rules\Core\RulesConditionBase;

/**
 * Provides a 'Webform has ID' condition.
 *
 * This condition will be added by default programmatically and will be hidden from the condition list.
 *
 * @Condition(
 *   id = "webform_name",
 *   label = @Translation("Webform name"),
 *   category = @Translation("A Webform"),
 *   context = {
 *     "selected_webform_id" = @ContextDefinition("string",
 *       label = @Translation("Selected webform")
 *     ),
 *     "submitted_webform_info" = @ContextDefinition("webform_info",
 *       label = @Translation("Submitted webform info")
 *     )
 *   }
 * )
 */
class WebformName extends RulesConditionBase {

  /**
   * Comparing id of selected and submitted webform.
   *
   * If the id of the selected webform (which was selected until a rule creation)
   * is the same as id of submitted webform then return TRUE.
   *
   * @param int $selected_webform_id
   *   Id of selected webform (which was selected until a rule creation).
   * @param array $submitted_webform_info
   *   Array with information about webform submission data (it contains id of the submitted webform).
   *
   * @return bool
   *   TRUE if id of the selected webform is the same as id of the submitted webform.
   */
  protected function doEvaluate($selected_webform_id, array $submitted_webform_info) {
    return ($selected_webform_id == $submitted_webform_info['id']);
  }

}
