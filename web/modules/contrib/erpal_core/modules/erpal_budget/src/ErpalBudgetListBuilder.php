<?php

/**
 * @file
 * Contains \Drupal\erpal_budget\ErpalBudgetListBuilder.
 */

namespace Drupal\erpal_budget;

use Drupal\Component\Utility\String;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a class to build a listing of erpal_budget entities.
 *
 * @see \Drupal\erpal_budget\Entity\ErpalBudget
 */
class ErpalBudgetListBuilder extends EntityListBuilder {

}
