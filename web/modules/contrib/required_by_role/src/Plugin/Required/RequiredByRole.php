<?php
/**
 * @file
 * Contains \Drupal\required_by_role\Plugin\Required\RequiredByRole.
 */

namespace Drupal\required_by_role\Plugin\Required;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\required_api\Annotation\Required;
use Drupal\required_api\Plugin\Required\RequiredBase;

/**
 *
 * @Required(
 *   id = "required_by_role",
 *   admin_label = @Translation("Required by role"),
 *   label = @Translation("Required by role"),
 *   description = @Translation("Required based on current user roles.")
 * )
 */
class RequiredByRole extends RequiredBase {

  /**
   * Determines wether a field is required or not.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   An field instance object.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   An account object.
   *
   * @return bool
   *   TRUE on required. FALSE otherwise.
   */
  public function isRequired(FieldDefinitionInterface $field, AccountInterface $account) {

    $available_roles = $account->getRoles();
    $field_roles = $field->getThirdPartySetting('required_api','required_plugin_options', []);

    $is_required = $this->getMatches($available_roles, $field_roles);
    return $is_required;
  }

  /**
   * Helper method to test if the role exists into the allowed ones.
   *
   * @param array $user_roles
   *   Roles belonging to the user.
   *
   * @param array $required_roles
   *   Roles that are required for this field.
   *
   * @return bool
   *   Wether or not the user have a required role.
   */
  public function getMatches($user_roles, $required_roles) {

    $match = array_intersect((array) $user_roles, (array) $required_roles);

    return !empty($match);
  }

  /**
   * Form element to build the required property.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   The field instance
   *
   * @return array
   *   Form element
   */
  public function requiredFormElement(FieldDefinitionInterface $field) {

    $roles = user_roles();
    $default_value = $field->getThirdPartySetting('required_api','required_plugin_options') ?: [];

    unset($roles[AccountInterface::AUTHENTICATED_ROLE]);

    $options = [];

    foreach ($roles as $role) {
      $options[$role->id()] = [
        'name' => $role->label(),
      ];
    }

    $header = [
      'name' => ['data' => t('Role')],
    ];

    $element = [
      '#type' => 'tableselect',
      '#header' => $header,
      '#options' => $options,
      '#default_value' => $default_value,
      '#js_select' => TRUE,
      '#multiple' => TRUE,
      '#empty' => t('No roles available.'),
      '#attributes' => [
        'class' => ['tableselect-required-by-role'],
      ],
    ];

    return $element;
  }

}
