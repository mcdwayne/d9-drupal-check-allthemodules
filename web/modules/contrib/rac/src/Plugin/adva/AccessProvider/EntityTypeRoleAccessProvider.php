<?php

namespace Drupal\rac\Plugin\adva\AccessProvider;

use Drupal\adva\Plugin\adva\EntityTypeAccessProvider;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\user\Entity\Role;

/**
 * Provides pre role access to entities based upon type and bundle.
 *
 * Entity Type Role Access exposes content to users based upon their role by
 * granting access if one of the users roles is configured for the entitie's
 * bundle.
 *
 * @AccessProvider(
 *   id = "rac_typed",
 *   label = @Translation("Entity Type Role Access"),
 *   operations = {
 *     "view",
 *     "update",
 *     "delete",
 *   },
 * )
 */
class EntityTypeRoleAccessProvider extends EntityTypeAccessProvider {

  /**
   * {@inheritdoc}
   */
  public function getAccessGrants($operation, AccountInterface $account) {
    $grants = [];
    $role_ids = $account->getRoles();
    foreach ($role_ids as $role_id) {
      $grants[$this->getPluginId() . '_' . $role_id] = [1];
    }
    return $grants;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOperationConfigForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildOperationConfigForm($form, $form_state);

    $op = $form['#entity_op'];
    $context = [
      '%op' => $op,
    ];
    $defaults = [];
    switch ($form['#entity_op_type']) {
      case EntityTypeAccessProvider::ENTITY_TYPE_OP:
        $defaults = isset($this->configuration['operations'][$op]) ? $this->configuration['operations'][$op] : [];
        break;

      case EntityTypeAccessProvider::ENTITY_DEFAULT_OP:
        $config = isset($this->configuration['default']['operations']) ? $this->configuration['default']['operations'] : [];
        $defaults = isset($config[$op]) ? $config[$op] : [];
        break;

      case EntityTypeAccessProvider::ENTITY_BUNDLE_OP:
        if (isset($form['#entity_bundle'])) {
          $config = isset($this->configuration['bundles']['override'][$form['#entity_bundle']]['operations']) ? $this->configuration['bundles']['override'][$form['#entity_bundle']]['operations'] : [];
          $defaults = isset($config[$op]) ? $config[$op] : [];
        }
        break;
    }

    $form_parents = $form['#parents'];
    $roles = Role::loadMultiple();
    $role_options = [];
    foreach ($roles as $role_id => $role) {
      $role_options[$role_id] = $role->label();
    }

    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Grant <em>%op</em> to users with...', $context),
      '#parents' => array_merge($form_parents, ['roles']),
      '#default_value' => isset($defaults['roles']) ? $defaults['roles'] : [],
      '#options' => $role_options,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitOperationConfigForm(array $form, FormStateInterface $form_state) {
    parent::submitOperationConfigForm($form, $form_state);

    $new_config = $form_state->getValues();
    if (isset($new_config['roles'])) {
      $new_config['roles'] = array_filter($new_config['roles']);
    }

    if (!isset($form['#entity_op']) || !isset($form['#entity_op_type'])) {
      return;
    }

    $op = $form['#entity_op'];
    $op_type = $form['#entity_op_type'];

    switch ($op_type) {
      case EntityTypeAccessProvider::ENTITY_TYPE_OP:
        $this->configuration['operations'][$op] = $new_config;
        break;

      case EntityTypeAccessProvider::ENTITY_DEFAULT_OP:
        $this->configuration['default']['operations'][$op] = $new_config;
        break;

      case EntityTypeAccessProvider::ENTITY_BUNDLE_OP:
        if (isset($form['#entity_bundle'])) {
          $bundle = $form['#entity_bundle'];
          $this->configuration['bundles']['override'][$bundle]['operations'][$op] = $new_config;
        }
        break;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getAccessRecordsFromConfig(array $config) {
    $all_ops = ['view', 'update', 'delete'];
    $configured_role_ops = [];

    foreach ($all_ops as $op) {
      if (isset($config[$op]['roles'])) {
        foreach ($config[$op]['roles'] as $role) {
          if (!isset($configured_role_ops[$role])) {
            $configured_role_ops[$role] = [];
          }
          if (!in_array($role, $configured_role_ops)) {
            $configured_role_ops[$role][] = $op;
          }
        }
      }
    }

    $records = [];
    foreach ($configured_role_ops as $role => $role_ops) {
      $record = [
        'realm' => $this->getPluginId() . '_' . $role,
        'gid' => 1,
      ];
      foreach ($all_ops as $op) {
        $record['grant_' . $op] = (in_array($op, $role_ops)) ? 1 : 0;
      }
      $records[] = $record;
    }
    return $records;
  }

}
