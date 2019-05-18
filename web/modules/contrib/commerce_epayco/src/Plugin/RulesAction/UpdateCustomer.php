<?php

namespace Drupal\commerce_epayco\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\commerce_epayco\Entity\CommerceEpaycoApiData;

/**
 * Provides integration with Rules to create a customer.
 *
 * @RulesAction(
 *   id = "rules_commerce_epayco_update_customer",
 *   label = @Translation("Update ePayco customer"),
 *   category = @Translation("Commerce ePayco"),
 *   context = {
 *     "configuration" = @ContextDefinition("entity:commerce_epayco_api_data",
 *       label = @Translation("Configuration entity")
 *     ),
 *     "id_customer" = @ContextDefinition("string",
 *       label = @Translation("Customer ID")
 *     ),
 *     "token_card" = @ContextDefinition("string",
 *       label = @Translation("Token card")
 *     ),
 *     "name" = @ContextDefinition("string",
 *       label = @Translation("Name")
 *     ),
 *     "email" = @ContextDefinition("string",
 *       label = @Translation("Email")
 *     ),
 *     "phone" = @ContextDefinition("string",
 *       label = @Translation("Phone")
 *     ),
 *     "default" = @ContextDefinition("boolean",
 *       label = @Translation("Default")
 *     )
 *   },
 *   provides = {
 *     "ep_update_customer_status" = @ContextDefinition("boolean",
 *        label = @Translation("Update customer status")
 *      ),
 *      "ep_update_customer_message" = @ContextDefinition("string",
 *        label = @Translation("Update customer message")
 *      )
 *   }
 * )
 */
class UpdateCustomer extends RulesActionBase {

  /**
   * Executes the plugin.
   *
   * @param \Drupal\commerce_epayco\Entity\CommerceEpaycoApiData $configuration
   *   Configuration entity. See admin/commerce/config/commerce-epayco/api-data.
   * @param string $id_customer
   *   ID of the customer to be updated.
   * @param string $token_card
   *   The card token previously created.
   * @param string $name
   *   The customer's full name.
   * @param string $email
   *   The customer's email.
   * @param string $phone
   *   The customer's phone.
   * @param bool $default
   *   If default customer.
   */
  protected function doExecute(CommerceEpaycoApiData $configuration, $id_customer, $token_card, $name, $email, $phone, $default) {
    $epayco = commerce_epayco_get_epayco_manager($configuration);
    $data = [
      'token_card' => $token_card,
      'name' => $name,
      'email' => $email,
      'phone' => $phone,
      'default' => $default,
    ];
    $customer = $epayco->updateCustomer($id_customer, $data);

    $this->setProvidedValue('ep_update_customer_status', isset($customer->status) ? $customer->status : FALSE);
    $this->setProvidedValue('ep_update_customer_message', isset($customer->message) ? $customer->message : '');
  }

}
