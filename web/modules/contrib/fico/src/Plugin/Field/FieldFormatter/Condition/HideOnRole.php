<?php
namespace Drupal\fico\Plugin\Field\FieldFormatter\Condition;

use Drupal\fico\Plugin\FieldFormatterConditionBase;
use Drupal\user\Entity\Role;

/**
 * The plugin for check empty fields.
 *
 * @FieldFormatterCondition(
 *   id = "hide_on_role",
 *   label = @Translation("Hide when current user has role"),
 *   dsFields = TRUE,
 *   types = {
 *     "all"
 *   }
 * )
 */
class HideOnRole extends FieldFormatterConditionBase {

  /**
   * {@inheritdoc}
   */
  public function alterForm(&$form, $settings) {
    $user_roles = [];
    foreach (Role::loadMultiple() as $role) {
      $user_roles[$role->id()] = $role->label();
    }
    $default_include = isset($settings['settings']['include_admin']) ? $settings['settings']['include_admin'] : NULL;
    $default_roles = isset($settings['settings']['roles']) ? $settings['settings']['roles'] : NULL;

    $form['include_admin'] = array(
      '#type' => 'checkbox',
      '#title' => t('Include the administrator'),
      '#default_value' => $default_include,
    );
    $form['roles'] = array(
      '#type' => 'select',
      '#multiple' => TRUE,
      '#title' => t('Select roles'),
      '#options' => $user_roles,
      '#default_value' => $default_roles,
    );
  }

  /**
   * {@inheritdoc}
   */
  public function access(&$build, $field, $settings) {
    if (array_intersect(\Drupal::currentUser()->getRoles(), $settings['settings']['roles']) && (\Drupal::currentUser()->id() != 1 || $settings['settings']['include_admin'] == 1)) {
      $build[$field]['#access'] = FALSE;
    };
  }

  /**
   * {@inheritdoc}
   */
  public function summary($settings) {
    $roles = [];
    foreach (Role::loadMultiple() as $role) {
      if (in_array($role->id(), $settings['settings']['roles'])) {
        $roles[] = $role->label();
      }
    }
    return t("Condition: %condition (%settings)", [
      "%condition" => t('Hide when current user has role'),
      '%settings' => implode(', ', $roles),
    ]);
  }

}
