<?php

namespace Drupal\commerce_epayco\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\commerce_epayco\Entity\CommerceEpaycoApiData;

/**
 * Provides integration with Rules to create a subscription.
 *
 * @RulesAction(
 *   id = "rules_commerce_epayco_charge_subscription",
 *   label = @Translation("Charge ePayco subscription"),
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
 *     "url_response" = @ContextDefinition("string",
 *       label = @Translation("URL response"),
 *       required = FALSE
 *     ),
 *     "url_confirmation" = @ContextDefinition("string",
 *       label = @Translation("URL confirmation"),
 *       required = FALSE
 *     ),
 *     "doc_type" = @ContextDefinition("string",
 *       label = @Translation("Personal ID type (CC, TI, CE...)")
 *     ),
 *     "doc_number" = @ContextDefinition("string",
 *       label = @Translation("Personal ID")
 *     )
 *   },
 *   provides = {
 *     "ep_charge_subscription_status" = @ContextDefinition("boolean",
 *        label = @Translation("Status")
 *     ),
 *     "ep_charge_subscription_message" = @ContextDefinition("string",
 *        label = @Translation("Message")
 *     )
 *   }
 * )
 */
class ChargeSubscription extends RulesActionBase {

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
   * @param string $url_response
   *   Response URL to redirect customer when action is finished.
   *   Default: https:/secure.payco.co/restpagos/testRest/endpagopse.php.
   * @param string $url_confirmation
   *   URL to send data to be stored into the server.
   *   Default: https:/secure.payco.co/restpagos/testRest/endpagopse.php.
   * @param string $doc_type
   *   Personal ID type. Example: "CC", "TI", "CE".
   * @param string $doc_number
   *   Personal ID.
   */
  protected function doExecute(CommerceEpaycoApiData $configuration, $id_plan, $customer, $token_card, $url_response, $url_confirmation, $doc_type, $doc_number) {
    $epayco = commerce_epayco_get_epayco_manager($configuration);
    $subscription = $epayco->chargeSubscription($id_plan, $customer, $token_card, $url_response, $url_confirmation, $doc_type, $doc_number);

    $this->setProvidedValue('ep_charge_subscription_status', isset($subscription->status) ? $subscription->status : FALSE);
    $this->setProvidedValue('ep_charge_subscription_message', isset($subscription->data->description) ? $subscription->data->description : '');
  }

}
