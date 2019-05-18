<?php

namespace Drupal\mcapi\Plugin\Field\FieldType;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\user\Entity\User;

/**
 * Defines the 'wallet_holder' computed entity field type.
 *
 *
 * @FieldType(
 *   id = "wallet_holder",
 *   label = @Translation("Wallet holder"),
 *   description = @Translation("Context aware wallets you can pay in/out of"),
 *   category = @Translation("Reference"),
 *   default_formatter = "entity_reference_label",
 *   list_class = "\Drupal\Core\Field\EntityReferenceFieldItemList",
 * )
 *
 */
class WalletHolderItemComputed extends EntityReferenceItem {

  /**
   * The holder of the wallet
   * @var ContentEntity
   */
  private $holder;

  /**
   * {@inheritdoc}
   */
  public function __get($property_name) {
    if ($property_name == 'entity') {
      if (!$this->holder) {
        if ($value = $this->getValue()) {
          $this->holder = \Drupal::entityTypeManager()
          ->getStorage($value['entity_type_id'])
          ->load($value['target_id']);
        }
      }
      return $this->holder;
    }
    parent::__get($property_name);
  }

  /**
   * {@inheritdoc}
   */
  public function getValue($include_computed = FALSE) {
    $wallet = $this->getEntity();
    // This value is as similar as we can get to a normal entity reference value
    // without having configured the target_type
    if ($wallet->holder_entity_type->value && $wallet->holder_entity_id->value) {
      $entity_type_id = $wallet->holder_entity_type->value;
      $entity_id = $wallet->holder_entity_id->value;
    }
    else {
      $entity_type_id = 'user';
      $entity_id = 1;
    }
    return [
      'entity_type_id' => $entity_type_id,
      'target_id' => $entity_id,
    ];
  }

  /**
   * {@inheritdoc}
   *
   * @note $value is an array as given above.
   */
  public function setValue($value, $notify = TRUE) {
    $wallet = $this->getEntity();
    if (is_array($value)) {
      $wallet->holder_entity_type->value = $value['entity_type_id'];
      $wallet->holder_entity_id->value = $value['target_id'];
    }
    else {
      $wallet->holder_entity_type->value = $value->getEntityTypeId();
      $wallet->holder_entity_id->value = $value->id();
    }
  }
}
