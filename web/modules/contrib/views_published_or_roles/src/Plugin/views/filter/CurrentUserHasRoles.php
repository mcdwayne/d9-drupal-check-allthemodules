<?php

namespace Drupal\views_published_or_roles\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Filter by published status and by role.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("current_user_has_roles")
 */
class CurrentUserHasRoles extends FilterPluginBase {

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * Display the filter on the administrative summary.
   */
  public function adminSummary() {
    $roles = NULL;
    if (is_array($this->value)) {
      $roles = array_keys($this->value);
      $roles = implode(' ', $roles);
    }
    return $this->operator . ' ' . $roles;
  }

  /**
   * Returns available role options.
   */
  private function getValueOptions() {
    $options = user_role_names(TRUE);
    unset($options[AccountInterface::AUTHENTICATED_ROLE]);

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#type' => 'select',
      '#title' => t('Select Role(s)'),
      '#options' => $this->getValueOptions(),
      '#default_value' => $this->value,
      '#multiple' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $table = 'node_field_data';
    $roles = [];
    if (is_array($this->value)) {
      $roles = array_keys($this->value);
    }
    $this->query->addWhereExpression(
      $this->options['group'],
      '***CURRENT_USER*** IN (SELECT ur.entity_id FROM {user__roles} ur WHERE ur.roles_target_id IN (:roles[]))', [':roles[]' => $roles]);
  }

  /**
   * Skip validation if no options have been chosen.
   */
  public function validate() {
    if (!empty($this->value)) {
      parent::validate();
    }
  }

}
