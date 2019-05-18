<?php

namespace Drupal\commerce_epayco\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\commerce_epayco\Entity\CommerceEpaycoApiData;

/**
 * Provides integration with Rules to create a subscription.
 *
 * @RulesAction(
 *   id = "rules_commerce_epayco_create_subscription",
 *   label = @Translation("Create ePayco subscription"),
 *   category = @Translation("Commerce ePayco"),
 *   context = {
 *     "configuration" = @ContextDefinition("entity:commerce_epayco_api_data",
 *       label = @Translation("Configuration entity")
 *     ),
 *     "id_plan" = @ContextDefinition("string",
 *       label = @Translation("ID plan")
 *     ),
 *     "customer" = @ContextDefinition("string",
 *       label = @Translation("ID customer")
 *     ),
 *     "token_card" = @ContextDefinition("string",
 *       label = @Translation("Token card")
 *     ),
 *     "doc_type" = @ContextDefinition("string",
 *       label = @Translation("Personal ID type (CC, TI, CE...)")
 *     ),
 *     "doc_number" = @ContextDefinition("string",
 *       label = @Translation("Personal ID")
 *     )
 *   },
 *   provides = {
 *     "ep_create_subscription_created" = @ContextDefinition("boolean",
 *        label = @Translation("Created")
 *     ),
 *     "ep_create_subscription_message" = @ContextDefinition("string",
 *        label = @Translation("Message")
 *     ),
 *     "ep_create_subscription_status" = @ContextDefinition("string",
 *        label = @Translation("Status")
 *     ),
 *     "ep_create_subscription_id" = @ContextDefinition("string",
 *        label = @Translation("Subscription ID")
 *     ),
 *     "ep_create_subscription_date_current_start" = @ContextDefinition("string",
 *        label = @Translation("Current period start")
 *     ),
 *     "ep_create_subscription_date_current_end" = @ContextDefinition("string",
 *        label = @Translation("Current period end")
 *     ),
 *     "ep_create_subscription_date_created" = @ContextDefinition("string",
 *        label = @Translation("Created date")
 *     )
 *   }
 * )
 */
class CreateSubscription extends RulesActionBase {

  /**
   * Executes the plugin.
   *
   * @param \Drupal\commerce_epayco\Entity\CommerceEpaycoApiData $configuration
   *   Configuration entity. See admin/commerce/config/commerce-epayco/api-data.
   * @param string $id_plan
   *   Provided plan identifier.
   * @param string $customer
   *   Customer ID in ePayco.
   * @param string $token_card
   *   Token card.
   * @param string $doc_type
   *   Personal ID type. Example: "CC", "TI", "CE".
   * @param string $doc_number
   *   Personal ID.
   */
  protected function doExecute(CommerceEpaycoApiData $configuration, $id_plan, $customer, $token_card, $doc_type, $doc_number) {
    $epayco = commerce_epayco_get_epayco_manager($configuration);
    $subscription = $epayco->createSubscription($id_plan, $customer, $token_card, $doc_type, $doc_number);

    $this->setProvidedValue('ep_create_subscription_created', isset($subscription->success) ? $subscription->success : FALSE);
    $this->setProvidedValue('ep_create_subscription_message', isset($subscription->message) ? $subscription->message : '');
    $this->setProvidedValue('ep_create_subscription_status', isset($subscription->status) ? $subscription->status : '');
    $this->setProvidedValue('ep_create_subscription_id', isset($subscription->id) ? $subscription->id : '');
    $this->setProvidedValue('ep_create_subscription_date_current_start', isset($subscription->current_period_start) ? $subscription->current_period_start : '');
    $this->setProvidedValue('ep_create_subscription_date_current_end', isset($subscription->current_period_end) ? $subscription->current_period_end : '');
    $this->setProvidedValue('ep_create_subscription_date_created', isset($subscription->created) ? $subscription->created : '');
  }

}
