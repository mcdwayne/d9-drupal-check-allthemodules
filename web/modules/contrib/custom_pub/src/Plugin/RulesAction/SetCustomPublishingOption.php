<?php

/**
 * @file
 * Contains \Drupal\custom_pub\Plugin\RulesAction\SetCustomPublishingOption.
 */

namespace Drupal\custom_pub\Plugin\RulesAction;

use Drupal\custom_pub\CustomPublishingOptionInterface;
use Drupal\rules\Core\RulesActionBase;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an action to trigger a custom publishing option.
 *
 * @RulesAction(
 *   id = "rules_set_custom_publishing_option",
 *   label = @Translation("Set a custom publishing option on a node"),
 *   category = @Translation("Content"),
 *   context = {
 *    "entity" = @ContextDefinition("entity",
 *       label = @Translation("Entity"),
 *       description = @Translation("Specifies the entity, which should be saved permanently.")
 *     ),
 *     "custom_publish_option" = @ContextDefinition("entity:custom_publish_option",
 *       label = @Translation("Custom Publishing Option"),
 *       description = @Translation("The custom publishing option to set.")
 *     )
 *   }
 * )
 */
class SetCustomPublishingOption extends RulesActionBase {

  /**
   * Sets the custom publishing option on a given entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to be saved.
   * @param \Drupal\custom_pub\CustomPublishingOptionInterface $custom_publish_option
   *   The option to set.
   */
  protected function doExecute(EntityInterface $entity, CustomPublishingOptionInterface $custom_publish_option) {
    $entity->{$custom_publish_option->id()} = true;
  }
}
