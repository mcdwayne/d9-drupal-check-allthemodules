<?php

namespace Drupal\context_manager\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Defines the Context Ruleset entity.
 *
 * @ConfigEntityType(
 *   id = "context_ruleset",
 *   label = @Translation("Context Ruleset"),
 *   config_prefix = "context_ruleset",
 *   admin_permission = "administer context manager",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   },
 * )
 */
class ContextRuleset extends ConfigEntityBase implements ContextRulesetInterface {

  /**
   * The Context Ruleset ID (unuque machine name).
   *
   * @var string
   */
  protected $id;

  /**
   * The Context Ruleset label.
   *
   * @var string
   */
  protected $label;

  /**
   * The description of the ruleset entity.
   *
   * @var string
   */
  protected $description;

  /**
   * The tag of the page entity.
   *
   * @var string
   */
  protected $tag;

}
