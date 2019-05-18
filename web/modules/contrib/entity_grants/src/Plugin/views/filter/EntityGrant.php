<?php

namespace Drupal\entity_grants\Plugin\views\filter;

use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;

/**
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("entity_grants_access")
 */
class EntityGrant extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function canExpose() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['value']['default'] = [];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getValueOptions() {
    $options = \Drupal::service('entity_grants.permissions')->getPermissions($this->definition['entity_type']);

    foreach ($options as $option_key => $option) {
      $this->valueOptions[$option_key] = $option['title'];
    }

    return $this->valueOptions;
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    if (empty($this->value)) {
      return;
    }
    $this->ensureMyTable();

    $current_user = \Drupal::currentUser();
    if (!$current_user->hasPermission('bypass entity grant access')) {
      $def = $this->definition;
      $def['table'] = 'entity_grants';
      $def['field'] = 'entity_id';
      $def['left_table'] = $this->tableAlias;
      $def['left_field'] = $this->realField;
      $def['adjusted'] = TRUE;
      $def['type'] = 'INNER';
      $join = Views::pluginManager('join')->createInstance('standard', $def);
      $alias = $def['table'] . '_' . $this->table;
      $this->alias = $this->query->addRelationship($alias, $join, $this->table, $this->relationship);
      $this->query->addWhere('AND', $alias . '.entity_type', $this->definition['entity_type']);
      if ($current_user->id() != 1) {
        $this->query->addWhere('AND', $alias . '.uid', \Drupal::currentUser()->id());
      }
      $this->query->addWhere('AND', "$alias.grant", array_values($this->value), $this->operator);
    }
  }

}
