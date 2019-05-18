<?php

namespace Drupal\commerce_epayco\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\commerce_epayco\Entity\CommerceEpaycoApiData;

/**
 * Provides integration with Rules to create a card token.
 *
 * @RulesAction(
 *   id = "rules_commerce_epayco_create_card_token",
 *   label = @Translation("Create ePayco card token"),
 *   category = @Translation("Commerce ePayco"),
 *   context = {
 *     "configuration" = @ContextDefinition("entity:commerce_epayco_api_data",
 *       label = @Translation("Configuration entity")
 *     ),
 *     "card_number" = @ContextDefinition("string",
 *       label = @Translation("Card number")
 *     ),
 *     "card_exp_year" = @ContextDefinition("integer",
 *       label = @Translation("Card expiration year")
 *     ),
 *     "card_exp_month" = @ContextDefinition("string",
 *       label = @Translation("Card expiration month")
 *     ),
 *     "card_cvc" = @ContextDefinition("string",
 *       label = @Translation("Card CVC")
 *     )
 *   },
 *   provides = {
 *     "ep_create_card_token_token" = @ContextDefinition("string",
 *        label = @Translation("Card token")
 *      )
 *   }
 * )
 */
class CreateCardToken extends RulesActionBase {

  /**
   * Executes the plugin.
   *
   * @param \Drupal\commerce_epayco\Entity\CommerceEpaycoApiData $configuration
   *   Configuration entity. See admin/commerce/config/commerce-epayco/api-data.
   * @param string $card_number
   *   The card number.
   * @param int $card_exp_year
   *   The card expiration year.
   * @param string $card_exp_month
   *   The card expiration month.
   * @param string $card_cvc
   *   The card security code.
   */
  protected function doExecute(CommerceEpaycoApiData $configuration, $card_number, $card_exp_year, $card_exp_month, $card_cvc) {
    $epayco = commerce_epayco_get_epayco_manager($configuration);
    $card_token = $epayco->createCardToken($card_number, $card_exp_year, $card_exp_month, $card_cvc);

    $this->setProvidedValue('ep_create_card_token_token', isset($card_token->data->id) ? $card_token->data->id : NULL);
  }

}
