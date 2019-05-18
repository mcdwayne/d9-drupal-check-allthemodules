<?php

namespace Drupal\decoupled_auth\Plugin\Validation\Constraint;

use Drupal\Component\Utility\Unicode;
use Drupal\user\Entity\Role;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\decoupled_auth\DecoupledAuthConfig;

/**
 * Checks if a user's email address is unique on the site.
 *
 * Applies within coupled users and decoupled users of specific roles (if
 * configured).
 *
 * @see \Drupal\Core\Validation\Plugin\Validation\Constraint\UniqueFieldValueValidator
 */
class DecoupledAuthUserMailUniqueValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($items, Constraint $constraint) {
    if (!$item = $items->first()) {
      return;
    }
    $field_name = $items->getFieldDefinition()->getName();
    /** @var \Drupal\decoupled_auth\Entity\DecoupledAuthUser $entity */
    $entity = $items->getEntity();
    $entity_type_id = $entity->getEntityTypeId();
    $id_key = $entity->getEntityType()->getKey('id');

    $config = \Drupal::config('decoupled_auth.settings');
    $mode = $config->get('unique_emails.mode');

    // If we are using a specific set of roles, get the inclusive list.
    if (in_array($mode, [DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITH_ROLE, DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITHOUT_ROLE])) {
      $selected_roles = $config->get('unique_emails.roles');
      $roles = user_role_names(TRUE);
      unset($roles[Role::AUTHENTICATED_ID]);
      $roles = array_keys($roles);

      if ($mode == DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITH_ROLE) {
        $roles = array_intersect($roles, $selected_roles);
      }
      else {
        $roles = array_diff($roles, $selected_roles);
      }

      // If our current user is decoupled and not in any of the selected roles,
      // we don't need to check anything as duplicates are allowed.
      if ($entity->isDecoupled() && ($mode == DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITH_ROLE xor array_intersect($entity->getRoles(), $selected_roles))) {
        return;
      }
    }
    // If our mode is
    // \Drupal\decoupled_auth\DecoupledAuthConfig::UNIQUE_EMAILS_MODE_COUPLED
    // and the user is decoupled, we don't need to do any further checks.
    elseif ($mode == DecoupledAuthConfig::UNIQUE_EMAILS_MODE_COUPLED && $entity->isDecoupled()) {
      return;
    }

    $query = \Drupal::entityQuery($entity_type_id)
      // The id could be NULL, so we cast it to 0 in that case.
      ->condition($id_key, (int) $items->getEntity()->id(), '<>')
      ->condition($field_name, $item->value);

    // Exclude mode needs to be dealt with specially as no roles is also a valid
    // match.
    if ($mode == DecoupledAuthConfig::UNIQUE_EMAILS_MODE_WITHOUT_ROLE) {
      $condition = $query->orConditionGroup()
        ->exists('name')
        ->notExists('roles');
      if (!empty($roles)) {
        $condition->condition('roles', $roles, 'IN');
      }
      $query->condition($condition);
    }
    // If we have some roles to filter on, we find anything that is either
    // coupled or in one of the given roles.
    elseif (!empty($roles)) {
      $condition = $query->orConditionGroup()
        ->exists('name')
        ->condition('roles', $roles);
      $query->condition($condition);
    }
    // Otherwise, if no decoupled users have to be unique, then we only have to
    // filter on whether the user is coupled.
    elseif ($mode != DecoupledAuthConfig::UNIQUE_EMAILS_MODE_ALL_USERS) {
      $query->exists('name');
    }

    $value_taken = (bool) $query
      ->range(0, 1)
      ->count()
      ->execute();

    if ($value_taken) {
      $this->context->addViolation($constraint->message, [
        '%value' => $item->value,
        '@entity_type' => $entity->getEntityType()->getLowercaseLabel(),
        '@field_name' => Unicode::strtolower($items->getFieldDefinition()->getLabel()),
      ]);
    }
  }

}
