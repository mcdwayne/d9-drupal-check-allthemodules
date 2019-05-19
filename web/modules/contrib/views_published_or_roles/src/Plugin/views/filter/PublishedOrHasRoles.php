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
 * @ViewsFilter("status_has_role")
 */
class PublishedOrHasRoles extends FilterPluginBase {

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
      '#size' => 30,
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
    if (empty($roles)) {
      // If there are no roles selected, not querying on roles.
      $this->query->addWhereExpression(
        $this->options['group'],
        "$table.status = 1
        OR
        ($table.uid = ***CURRENT_USER*** AND ***CURRENT_USER*** <> 0 AND ***VIEW_OWN_UNPUBLISHED_NODES*** = 1)
        OR
        ***BYPASS_NODE_ACCESS*** = 1");
    }
    else {
      $this->query->addWhereExpression(
        $this->options['group'],
        "$table.status = 1
        OR
        ($table.uid = ***CURRENT_USER*** AND ***CURRENT_USER*** <> 0 AND ***VIEW_OWN_UNPUBLISHED_NODES*** = 1)
        OR
        ***BYPASS_NODE_ACCESS*** = 1
        OR
        ***CURRENT_USER*** IN (SELECT ur.entity_id FROM {user__roles} ur WHERE ur.roles_target_id IN (:roles[]))", [':roles[]' => $roles]);
    }
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
