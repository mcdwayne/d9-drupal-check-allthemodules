<?php

namespace Drupal\memory_limit_policy\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\memory_limit_policy\MemoryLimitPolicyInterface;

/**
 * Defines a Memory Limit Policy configuration entity class.
 *
 * @ConfigEntityType(
 *   id = "memory_limit_policy",
 *   label = @Translation("Memory Limit Policy"),
 *   label_singular = @Translation("Memory Limit Policy"),
 *   label_plural = @Translation("Memory Limit Policies"),
 *   label_count = @PluralTranslation(
 *     singular = @Translation("memory limit policy"),
 *     plural = @Translation("memory limit policies"),
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\memory_limit_policy\Controller\MemoryLimitPolicyListBuilder",
 *     "form" = {
 *       "delete" = "Drupal\memory_limit_policy\Form\MemoryLimitPolicyDeleteForm"
 *     },
 *     "wizard" = {
 *       "add" = "Drupal\memory_limit_policy\Wizard\MemoryLimitPolicyWizard",
 *       "edit" = "Drupal\memory_limit_policy\Wizard\MemoryLimitPolicyWizard"
 *     }
 *   },
 *   config_prefix = "memory_limit_policy",
 *   admin_permission = "administer site configuration",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *     "weight" = "weight"
 *   },
 *   links = {
 *     "canonical" = "/admin/config/performance/memory-limit-policy/{memory_limit_policy}",
 *     "edit-form" = "/admin/config/performance/memory-limit-policy/{machine_name}/{step}",
 *     "delete-form" = "/admin/config/performance/memory-limit-policy/policy/delete/{memory_limit_policy}",
 *     "collection" = "/admin/config/performance/memory-limit-policy/list"
 *   }
 * )
 */
class MemoryLimitPolicy extends ConfigEntityBase implements MemoryLimitPolicyInterface {

  /**
   * The ID of the memory limit policy.
   *
   * @var int
   */
  protected $id;

  /**
   * The policy title.
   *
   * @var string
   */
  protected $label;

  /**
   * Constraint instance IDs.
   *
   * @var array
   */
  protected $policy_constraints = [];

  /**
   * The memory for this policy.
   *
   * @var int
   */
  protected $memory;

  /**
   * The weight for this policy.
   *
   * @var int
   */
  protected $weight;

  /**
   * The status for this policy.
   *
   * @var bool
   */
  protected $status;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->id;
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    return $this->label;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight() {
    return $this->weight;
  }

  /**
   * Return the constraints from the policy.
   *
   * @return array
   *   The policies constraints.
   */
  public function getConstraints() {
    return $this->policy_constraints;
  }

  /**
   * Return a specific constraint from the policy.
   *
   * @param int $key
   *   The constraint index in constraints list.
   *
   * @return \Drupal\memory_limit_policy\MemoryLimitConstraintInterface
   *   A specific constraint in the policy.
   */
  public function getConstraint($key) {
    if (!isset($this->policy_constraints[$key])) {
      return NULL;
    }
    return $this->policy_constraints[$key];
  }

  /**
   * Return the memory settings from the policy.
   *
   * @return int
   *   The memory to set.
   */
  public function getMemory() {
    return $this->memory;
  }

  /**
   * Evaluate the policy to check if it applies.
   *
   * @return bool
   *   TRUE if the policy applies, FALSE otherwise.
   */
  public function evaluate() {
    foreach ($this->getConstraints() as $constraint) {
      $plugin = \Drupal::service('plugin.manager.memory_limit_policy.memory_limit_constraint');

      /** @var \Drupal\memory_limit_policy\MemoryLimitConstraintInterface $constraint */
      $constraint = $plugin->createInstance($constraint['id'], $constraint);

      if (!$constraint->evaluate()) {
        return FALSE;
      }
    }

    return TRUE;
  }

}
