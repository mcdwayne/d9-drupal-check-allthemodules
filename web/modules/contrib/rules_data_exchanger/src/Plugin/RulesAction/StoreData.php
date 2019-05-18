<?php

namespace Drupal\rules_data_exchanger\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;

/**
 * Provides the action for storing a data for using in rules components or other rules.
 *
 * The "rule_id" context is using for storing a id of a current rule in which a storing a data occurs.
 * It enables to know which stored data needs to be deleted if 'rules_data_exchanger_store_data action' will be deleted.
 * It sets programmatically and therefore its hidden in a form.
 *
 * @RulesAction(
 *   id = "rules_data_exchanger_store_data",
 *   label = @Translation("Store data"),
 *   category = @Translation("Data"),
 *   context = {
 *     "data" = @ContextDefinition("any",
 *       label = @Translation("Data for storing"),
 *       required = TRUE,
 *       assignment_restriction = "selector"
 *     ),
 *     "name" = @ContextDefinition("string",
 *       label = @Translation("Name"),
 *       required = TRUE,
 *       description = @Translation("Think of a name for the variable in which a data will be store."),
 *       assignment_restriction = "input"
 *     ),
 *     "rule_id" = @ContextDefinition("string")
 *   }
 * )
 */
class StoreData extends RulesActionBase {

  /**
   * Store a data.
   *
   * @param mixed $data
   *   The stored data.
   * @param string $name
   *   The name of the stored data.
   */
  protected function doExecute($data, $name) {
    // trim($name) is needed to prevent of creation invisible names (which consist of white spaces or tabs).
    if (isset($data) && trim($name)) {

      $stored_data = \Drupal::state()->get('rules_data_exchanger.stored_data');

      if (!isset($stored_data)) {
        $stored_data = [];
      }

      $stored_data[$name]['data'] = $data;
      \Drupal::state()->set('rules_data_exchanger.stored_data', $stored_data);
    }
  }

  /**
   * Store data type to use in StoredDataContex class.
   */
  public function refineContextDefinitions(array $selected_data) {

    $name = $this->getContextValue('name');
    $rule_id = $this->getContextValue('rule_id');
    // trim($name) is needed to prevent of creation invisible names (which consist of white spaces or tabs).
    if (isset($selected_data['data']) && trim($name) && $rule_id) {

      $stored_data = \Drupal::state()->get('rules_data_exchanger.stored_data');

      if (!isset($stored_data)) {
        $stored_data = [];
      }

      $type = $selected_data['data']->getDataType();

      $stored_data[$name] = [
        'data' => NULL,
        'type' => $type,
        'rule_id' => $rule_id,
      ];

      \Drupal::state()->set('rules_data_exchanger.stored_data', $stored_data);
    }
  }

}
