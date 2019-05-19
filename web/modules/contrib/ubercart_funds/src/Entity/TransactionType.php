<?php

namespace Drupal\ubercart_funds\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Transaction type.
 *
 * @ingroup ubercart_funds
 *
 * @ConfigEntityType(
 *   id = "ubercart_funds_transaction_type",
 *   label = @Translation("Transaction type"),
 *   label_collection = @Translation("Transaction types"),
 *   label_singular = @Translation("transaction type"),
 *   label_plural = @Translation("transaction types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count transaction type",
 *     plural = "@count transaction types",
 *   ),
 *   handlers = {
 *     "access" = "Drupal\ubercart_funds\TransactionBundleAccessControlHandler",
 *   },
 *   bundle_of = "ubercart_funds_transaction",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   config_prefix = "ubercart_funds_transaction_type",
 *   config_export = {
 *     "id",
 *     "label",
 *     "description",
 *   },
 * )
 */
class TransactionType extends ConfigEntityBundleBase {

}
