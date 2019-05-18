<?php

namespace Drupal\rules_data_exchanger\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * Provides the action for clearing of a stored data.
 *
 * The "data" context stores a full name of stored data to be cleared.
 * '@stored_data:my_variable' - the example of the full name of a stored data.
 * The "key" context is using for storing of a key of a storing data which need to be cleared.
 * This key extracts from a full name of stored data.
 * 'my_variable' - the example of the key.
 * The key extracts and sets programmatically in a form's validate handler.
 * Therefore its field is hidden on action edit form.
 * Also keep in mind that we intentionally did not use 'required = TRUE' for "data" context.
 * We did so because after data clearing sometimes error message appears that the context data requires a value.
 *
 * @RulesAction(
 *   id = "rules_data_exchanger_clear_stored_data",
 *   label = @Translation("Clear stored data"),
 *   category = @Translation("Data"),
 *   context = {
 *     "data" = @ContextDefinition("any",
 *       label = @Translation("Data to be cleared"),
 *       description = @Translation("Select a name of a stored data that need to be cleared."),
 *       assignment_restriction = "selector"
 *     ),
 *     "key" = @ContextDefinition("string")
 *   }
 * )
 */
class ClearStoredData extends RulesActionBase {

  /**
   * Delete stored data.
   */
  protected function doExecute($data, $key) {
    if (isset($data) && isset($key)) {
      $this
        ->getContext('data')
        ->getContextData()
        ->setValue(NULL);

      $stored_data = \Drupal::state()->get('rules_data_exchanger.stored_data');
      $stored_data[$key]['data'] = NULL;
      \Drupal::state()->set('rules_data_exchanger.stored_data', $stored_data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function refineContextDefinitions(array $selected_data) {
  }

}
