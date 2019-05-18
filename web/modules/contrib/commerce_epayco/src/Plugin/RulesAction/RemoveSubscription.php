<?php

namespace Drupal\commerce_epayco\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\commerce_epayco\Entity\CommerceEpaycoApiData;

/**
 * Provides integration with Rules to remove a subscription.
 *
 * @RulesAction(
 *   id = "rules_commerce_epayco_remove_subscription",
 *   label = @Translation("Remove ePayco subscription"),
 *   category = @Translation("Commerce ePayco"),
 *   context = {
 *     "configuration" = @ContextDefinition("entity:commerce_epayco_api_data",
 *       label = @Translation("Configuration entity")
 *     ),
 *     "id_client" = @ContextDefinition("string",
 *       label = @Translation("ID customer")
 *     )
 *   },
 *   provides = {
 *     "ep_removed_subscription_status" = @ContextDefinition("boolean",
 *        label = @Translation("Removed status")
 *     ),
 *     "ep_removed_subscription_message" = @ContextDefinition("string",
 *        label = @Translation("Removed message")
 *     )
 *   }
 * )
 */
class RemoveSubscription extends RulesActionBase {

  /**
   * Executes the plugin.
   *
   * @param \Drupal\commerce_epayco\Entity\CommerceEpaycoApiData $configuration
   *   Configuration entity. See admin/commerce/config/commerce-epayco/api-data.
   * @param string $id_client
   *   Customer to cancel the subscription to.
   */
  protected function doExecute(CommerceEpaycoApiData $configuration, $id_client) {
    $epayco = commerce_epayco_get_epayco_manager($configuration);
    $subscription = $epayco->removeSubscription($id_client);

    $this->setProvidedValue('ep_removed_subscription_status', isset($subscription->success) ? $subscription->success : FALSE);
    $this->setProvidedValue('ep_removed_subscription_message', isset($subscription->data->description) ? $subscription->data->description : '');
  }

}
