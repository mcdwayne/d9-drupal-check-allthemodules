<?php

namespace Drupal\gnode_field\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\filter\InOperator;
use Drupal\views\ViewExecutable;

/**
 * Filter to sort groups.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("gnode_field_filter")
 */
class GroupsFilter extends InOperator {

  /**
   * {@inheritdoc}
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);
    $this->valueTitle = t('Groups');
    $this->definition['options callback'] = [$this, 'generateOptions'];
    $this->definition['options arguments'] = 'derp';
  }

  /**
   * {@inheritdoc}
   */
  public function generateOptions() {
    // Only load groups that the current user is part of.
    /** @var \Drupal\gnode_field\Service\GroupNodeFieldService $gnodeField */
    $groups = \Drupal::service('gnode_field.node_group_ref');
    $group_ids = array_keys($groups->memberGroups);
    /** @var \Drupal\Core\Entity\EntityStorageBase $storage */
    $groups = \Drupal::entityTypeManager()->getStorage('group')->loadMultiple($group_ids);

    $options = [];
    foreach ($groups as $group) {
      $options[$group->label()] = $group->label();
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    $form['value']['#title'] = 'Filter Options are being set in the GroupsFilter plugin';
  }

  /**
   * {@inheritdoc}
   */
  protected function opSimple() {
    $this->realField = 'label';
    parent::opSimple();
  }

}
