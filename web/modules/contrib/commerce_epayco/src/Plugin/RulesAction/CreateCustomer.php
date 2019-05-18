<?php

namespace Drupal\commerce_epayco\Plugin\RulesAction;

use Drupal\rules\Core\RulesActionBase;
use Drupal\commerce_epayco\Entity\CommerceEpaycoApiData;

/**
 * Provides integration with Rules to create a customer.
 *
 * @RulesAction(
 *   id = "rules_commerce_epayco_create_customer",
 *   label = @Translation("Create ePayco customer"),
 *   category = @Translation("Commerce ePayco"),
 *   context = {
 *     "configuration" = @ContextDefinition("entity:commerce_epayco_api_data",
 *       label = @Translation("Configuration entity")
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
 *     "ep_create_customer_status" = @ContextDefinition("boolean",
 *        label = @Translation("Returned created customer status")
 *      ),
 *     "ep_create_customer_message" = @ContextDefinition("string",
 *        label = @Translation("Returned customer message")
 *      ),
 *     "ep_create_customer_id" = @ContextDefinition("string",
 *        label = @Translation("Returned customer ID")
 *      )
 *   }
 * )
 */
class CreateCustomer extends RulesActionBase {

  /**
   * Executes the plugin.
   *
   * @param \Drupal\commerce_epayco\Entity\CommerceEpaycoApiData $configuration
   *   Configuration entity. See admin/commerce/config/commerce-epayco/api-data.
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
  protected function doExecute(CommerceEpaycoApiData $configuration, $token_card, $name, $email, $phone, $default) {
    $epayco = commerce_epayco_get_epayco_manager($configuration);
    $customer = $epayco->createCustomer($token_card, $name, $email, $phone, $default);

    $this->setProvidedValue('ep_create_customer_status', isset($customer->status) ? $customer->status : FALSE);
    $this->setProvidedValue('ep_create_customer_message', isset($customer->message) ? $customer->message : '');
    $this->setProvidedValue('ep_create_customer_id', isset($customer->data->customerId) ? $customer->data->customerId : '');
  }

}
