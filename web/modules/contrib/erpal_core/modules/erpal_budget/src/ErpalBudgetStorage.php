<?php

/**
 * @file
 * Contains \Drupal\erpal_budget\ErpalBudgetStorage.
 */

namespace Drupal\erpal_budget;

use Drupal\Core\Entity\Sql\SqlContentEntityStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Language\LanguageInterface;

/**
 * Defines the controller class for nodes.
 *
 * This extends the base storage class, adding required special handling for
 * erpal_budget entities.
 */
class ErpalBudgetStorage extends SqlContentEntityStorage implements ErpalBudgetStorageInterface {
}
