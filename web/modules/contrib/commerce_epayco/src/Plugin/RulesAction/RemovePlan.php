<?php

namespace Drupal\commerce_epayco\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\commerce_epayco\Entity\CommerceEpaycoApiData;

/**
 * Provides integration with Rules to remove a plan.
 *
 * @RulesAction(
 *   id = "rules_commerce_epayco_remove_plan",
 *   label = @Translation("Remove ePayco plan"),
 *   category = @Translation("Commerce ePayco"),
 *   context = {
 *     "configuration" = @ContextDefinition("entity:commerce_epayco_api_data",
 *       label = @Translation("Configuration entity")
 *     ),
 *     "id_plan" = @ContextDefinition("string",
 *       label = @Translation("ID plan")
 *     )
 *   },
 *   provides = {
 *     "ep_removed_status" = @ContextDefinition("boolean",
 *        label = @Translation("Removed status")
 *     ),
 *     "ep_removed_message" = @ContextDefinition("string",
 *        label = @Translation("Removed message")
 *     )
 *   }
 * )
 */
class RemovePlan extends RulesActionBase {

  /**
   * Executes the plugin.
   *
   * @param \Drupal\commerce_epayco\Entity\CommerceEpaycoApiData $configuration
   *   Configuration entity. See admin/commerce/config/commerce-epayco/api-data.
   * @param string $id_plan
   *   Provided plan identifier.
   */
  protected function doExecute(CommerceEpaycoApiData $configuration, $id_plan) {
    $epayco = commerce_epayco_get_epayco_manager($configuration);
    $plan = $epayco->removePlan($id_plan);

    $this->setProvidedValue('ep_removed_status', isset($plan->status) ? $plan->status : FALSE);
    $this->setProvidedValue('ep_removed_message', isset($plan->message) ? $plan->message : '');
  }

}
