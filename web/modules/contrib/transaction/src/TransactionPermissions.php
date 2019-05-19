<?php

namespace Drupal\transaction;

use Drupal\Core\Routing\UrlGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\transaction\Entity\TransactionType;

/**
 * Provides dynamic permissions for transactions of different types.
 */
class TransactionPermissions {

  use StringTranslationTrait;
  use UrlGeneratorTrait;

  /**
   * Returns an array of transaction type permissions.
   *
   * @return array
   *   The transaction type permissions.
   *   @see \Drupal\user\PermissionHandlerInterface::getPermissions()
   */
  public function transactionTypePermissions() {
    $perms = [];
    // Generate transaction permissions for all transaction types.
    foreach (TransactionType::loadMultiple() as $type) {
      $perms += $this->buildPermissions($type);
    }

    return $perms;
  }

  /**
   * Returns a list of transaction permissions for a given transaction type.
   *
   * @param \Drupal\transaction\TransactionTypeInterface $type
   *   The transaction type.
   *
   * @return array
   *   An associative array of permission names and descriptions.
   */
  protected function buildPermissions(TransactionTypeInterface $type) {
    $type_id = $type->id();
    $type_params = ['%type_name' => $type->label()];

    return [
      "create $type_id transaction" => [
        'title' => $this->t('%type_name: Create new transaction', $type_params),
      ],
      "view own $type_id transaction" => [
        'title' => $this->t('%type_name: View own transaction', $type_params),
      ],
      "view any $type_id transaction" => [
        'title' => $this->t('%type_name: View any transaction', $type_params),
      ],
      "edit own $type_id transaction" => [
        'title' => $this->t('%type_name: Edit own transaction', $type_params),
      ],
      "edit any $type_id transaction" => [
        'title' => $this->t('%type_name: Edit any transaction', $type_params),
      ],
      "delete own $type_id transaction" => [
        'title' => $this->t('%type_name: Delete own transaction', $type_params),
      ],
      "delete any $type_id transaction" => [
        'title' => $this->t('%type_name: Delete any transaction', $type_params),
      ],
      "execute own $type_id transaction" => [
        'title' => $this->t('%type_name: Execute own transaction', $type_params),
      ],
      "execute any $type_id transaction" => [
        'title' => $this->t('%type_name: Execute any transaction', $type_params),
      ],
    ];
  }

}
