<?php

/**
 * @file
 * Contains \Drupal\erpal_budget\ErpalBudgetInterface.
 */

namespace Drupal\erpal_budget;

use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\user\UserInterface;

/**
 * Provides an interface defining a erpal_budget entity.
 */
interface ErpalBudgetInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

}
