<?php

namespace Drupal\rules_webform\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * Action for altering of webform submission value.
 *
 * This action can be used in all webform events.
 * The difference is that for 'Submission update' event 'Save' is not needed.
 * Because this event fires from hook_webform_submission_presave().
 * To know in which event this action uses we store event name in "event_name" context.
 * "event_name" sets programmatically in hook_form_FORM_ID_alter().
 * Therefore its field is hidden on action edit form.
 *
 * @RulesAction(
 *   id = "set_webform_field_value",
 *   label = @Translation("Set webform field value"),
 *   category = @Translation("A Webform"),
 *   context = {
 *     "field" = @ContextDefinition("any",
 *       label = @Translation("Webform field"),
 *       required = TRUE,
 *       assignment_restriction = "selector"
 *     ),
 *     "value" = @ContextDefinition("any",
 *       label = @Translation("Value"),
 *       required = TRUE
 *     ),
 *     "event_name" = @ContextDefinition("string")
 *   }
 * )
 */
class SetWebformFieldValue extends RulesActionBase {

  /**
   * Set a webform field value.
   *
   * @param mixed $field
   *   A webform field.
   * @param mixed $value
   *   New value of a webform field.
   * @param string $event_name
   *   An event name.
   */
  protected function doExecute($field, $value, $event_name) {
    $submission = \Drupal::state()->get('rules_webform.submission');
    // Do nothing if a submission has been removed by 'delete_webform_submission' action.
    if (!isset($submission) || !$event_name) {
      return;
    }

    $data = $submission->getData();

    $field_name = $this
      ->getContexts()['field']
      ->getContextData()
      ->getName();

    if (array_key_exists($field_name, $data)) {
      $data[$field_name] = $value;
      $submission->setData($data);

      // The 'submission_update' event fires from hook_webform_submission_presave().
      // Therefore we don't have to save changes. Saving is needed for events like 'webform_submit' and others.
      if ($event_name != 'updating_submission') {
        $submission->save();
      }
    }
  }

}
